<?php

namespace EvolutionCMS\aSocialAuth\Support;

use EvolutionCMS\aSocialAuth\Enums\SocialProvider;
use EvolutionCMS\aSocialAuth\Models\RegisteredProvider;
use Illuminate\Support\Collection;

/**
 * Keeps the `social_providers` table in step with the catalogue and the config.
 *
 * Three things describe a provider and each owns a different part:
 *
 *   the enum      – what HybridAuth can do: adapter class, default label, whether
 *                   an e-mail is ever returned, which credentials are required.
 *   the config    – what this site chose: which slugs are on, their credentials,
 *                   label and order overrides, any custom adapters.
 *   the table     – the joinable projection of the two, so identity rows can
 *                   carry a foreign key instead of a slug string.
 *
 * Credentials deliberately stop at the config. They are never written to the
 * table: a database dump of a CMS is a routine artefact — shared with a host,
 * copied to staging, mailed to a developer — and OAuth client secrets have no
 * business travelling with it. The table holds only what is safe to read.
 *
 * Syncing is lazy and fingerprinted. The config is hashed, and the table is
 * rewritten only when that hash changes, so the steady state costs one cache
 * read rather than a write per request.
 */
class ProviderRegistry
{
    protected const FINGERPRINT_KEY = 'asocialauth.registry.fingerprint';

    /** Guards against syncing more than once in a single request. */
    protected static bool $syncedThisRequest = false;

    /** Memoised per request; the table does not change under us mid-request. */
    protected static ?Collection $enabledCache = null;

    /**
     * Providers the site offers, ordered, ready to render as buttons.
     *
     * @return Collection<int, RegisteredProvider>
     */
    public static function enabled(): Collection
    {
        if (static::$enabledCache !== null) {
            return static::$enabledCache;
        }

        static::syncIfStale();

        try {
            $providers = RegisteredProvider::query()->enabled()->ordered()->get();
        } catch (\Throwable $e) {
            // Migrations not run yet: no providers is the honest answer, and it
            // must not take the login page down with it.
            Log::warning('Provider registry unavailable: ' . $e->getMessage());

            return static::$enabledCache = new Collection();
        }

        // A provider whose credentials went missing must stop being offered even
        // though its row says enabled — the button would only lead to a failure.
        $usable = $providers->filter(
            static fn (RegisteredProvider $provider) => static::hasRequiredKeys($provider->slug)
        )->values();

        return static::$enabledCache = $usable;
    }

    /**
     * An enabled provider by slug, or null. This is the lookup the controllers
     * use, so a disabled or unknown slug becomes a 404 rather than an attempt.
     */
    public static function resolve(?string $slug): ?RegisteredProvider
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        $slug = strtolower(trim($slug));

        return static::enabled()->first(
            static fn (RegisteredProvider $provider) => $provider->slug === $slug
        );
    }

    /**
     * Any registered provider by slug, enabled or not. The identities widget
     * needs this: an identity linked to a provider the site has since switched
     * off should still be listed, and still be removable.
     */
    public static function find(?string $slug): ?RegisteredProvider
    {
        static::syncIfStale();

        try {
            return RegisteredProvider::findBySlug($slug);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * The credential pair for a slug, straight from config.
     *
     * @return array{id?: string, secret?: string}
     */
    public static function credentials(string $slug): array
    {
        $config = static::configured()[strtolower($slug)] ?? [];
        $keys   = $config['keys'] ?? [];

        return is_array($keys) ? $keys : [];
    }

    /**
     * The full config entry for a slug — scope, extra adapter options, label.
     */
    public static function configFor(string $slug): array
    {
        return static::configured()[strtolower($slug)] ?? [];
    }

    /**
     * Whether every credential this provider requires is actually filled in.
     */
    public static function hasRequiredKeys(string $slug): bool
    {
        $case     = SocialProvider::fromSlug($slug);
        $required = $case?->requiredKeys() ?? ['id', 'secret'];

        if ($required === []) {
            return true;
        }

        $keys = static::credentials($slug);

        foreach ($required as $key) {
            if (trim((string) ($keys[$key] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * The site's provider configuration, normalised into one slug-keyed map.
     *
     * @return array<string, array>
     */
    public static function configured(): array
    {
        $providers = (array) config('cms.settings.aSocialAuth.providers', []);
        $custom    = (array) config('cms.settings.aSocialAuth.custom_providers', []);

        $out = [];

        foreach ([$providers, $custom] as $set) {
            foreach ($set as $slug => $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $slug = strtolower(trim((string) $slug));

                if ($slug === '' || !preg_match('/^[a-z0-9]+$/', $slug)) {
                    continue;
                }

                // Later entries win, so a custom_providers entry can override a
                // scaffolded one without the site having to delete it first.
                $out[$slug] = array_merge($out[$slug] ?? [], $entry);
            }
        }

        return $out;
    }

    /**
     * Rewrite the table from the config, but only when the config changed.
     */
    public static function syncIfStale(): void
    {
        if (static::$syncedThisRequest) {
            return;
        }

        $fingerprint = static::fingerprint();
        $cache       = static::cache();

        if ($cache !== null) {
            try {
                if ($cache->get(static::FINGERPRINT_KEY) === $fingerprint) {
                    static::$syncedThisRequest = true;

                    return;
                }
            } catch (\Throwable $e) {
                // Cache unusable; fall through and sync.
            }
        }

        if (static::sync()) {
            static::$syncedThisRequest = true;

            if ($cache !== null) {
                try {
                    $cache->forever(static::FINGERPRINT_KEY, $fingerprint);
                } catch (\Throwable $e) {
                    // Not fatal: the next request re-syncs instead.
                }
            }
        }
    }

    /**
     * Bring the table in line with the config.
     *
     * Rows are upserted rather than replaced, and rows for slugs the config no
     * longer mentions are disabled rather than deleted — deleting one would
     * orphan every identity linked to it, and a provider is switched off far
     * more often than it is truly retired.
     *
     * @return bool whether the sync completed (false when the table is missing)
     */
    public static function sync(): bool
    {
        $configured = static::configured();

        try {
            $existing = RegisteredProvider::query()->get()->keyBy('slug');
        } catch (\Throwable $e) {
            Log::warning('Provider registry sync skipped: ' . $e->getMessage());

            return false;
        }

        foreach ($configured as $slug => $entry) {
            $case    = SocialProvider::fromSlug($slug);
            $adapter = $entry['adapter'] ?? $case?->hybridauthProvider();

            if ($adapter === null) {
                // A slug that is neither catalogued nor given an adapter cannot
                // be turned into anything callable.
                Log::warning("Social provider '{$slug}' has no adapter and is not in the catalogue; skipped.");
                continue;
            }

            $attributes = [
                'adapter' => (string) $adapter,
                'label'   => (string) ($entry['label'] ?? $case?->label() ?? ucfirst($slug)),
                'enabled' => (bool) ($entry['enabled'] ?? false),
                'sort'    => (int) ($entry['sort'] ?? 0),
                'icon'    => isset($entry['icon']) ? (string) $entry['icon'] : null,
            ];

            try {
                $row = $existing->get($slug);

                if ($row === null) {
                    RegisteredProvider::query()->create($attributes + ['slug' => $slug]);
                    continue;
                }

                $row->fill($attributes);

                if ($row->isDirty()) {
                    $row->save();
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to register social provider '{$slug}': " . $e->getMessage());
            }
        }

        // Anything registered but no longer configured stops being offered.
        $dropped = $existing->keys()->diff(array_keys($configured));

        if ($dropped->isNotEmpty()) {
            try {
                RegisteredProvider::query()
                    ->whereIn('slug', $dropped->all())
                    ->where('enabled', true)
                    ->update(['enabled' => false, 'updated_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning('Failed to disable removed social providers: ' . $e->getMessage());
            }
        }

        static::$enabledCache = null;

        return true;
    }

    /**
     * Drop the memoised state. Tests and long-running workers need this after
     * changing config between calls.
     */
    public static function flush(): void
    {
        static::$enabledCache     = null;
        static::$syncedThisRequest = false;

        $cache = static::cache();

        if ($cache !== null) {
            try {
                $cache->forget(static::FINGERPRINT_KEY);
            } catch (\Throwable $e) {
                // Nothing to forget.
            }
        }
    }

    /**
     * A hash of everything in the config that the table projects. Credentials
     * are included so that filling in a missing secret re-runs the sync, but
     * only as part of the digest — the secret itself is never stored.
     */
    protected static function fingerprint(): string
    {
        $configured = static::configured();
        ksort($configured);

        return hash('sha256', (string) json_encode($configured));
    }

    /**
     * @return \Illuminate\Contracts\Cache\Repository|null
     */
    protected static function cache()
    {
        try {
            return app('cache.store');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
