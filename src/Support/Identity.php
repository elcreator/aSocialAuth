<?php

namespace EvolutionCMS\aSocialAuth\Support;

use EvolutionCMS\aSocialAuth\Models\RegisteredProvider;
use EvolutionCMS\aSocialAuth\Models\SocialAccount;
use Illuminate\Support\Collection;

/**
 * Who the visitor currently is, and which provider identities they own.
 *
 * Evolution CMS keeps its two sessions side by side — `mgrValidated` /
 * `mgrInternalKey` for the manager, `webValidated` / `webInternalKey` for the
 * front end — and a visitor can legitimately hold both at once. Everything here
 * is therefore asked per context rather than globally.
 */
class Identity
{
    /**
     * The logged-in user id for a context, or null when there is no session.
     */
    public static function currentUserId(string $context): ?int
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            return null;
        }

        if (empty($_SESSION[$context . 'Validated'])) {
            return null;
        }

        $id = (int) ($_SESSION[$context . 'InternalKey'] ?? 0);

        return $id > 0 ? $id : null;
    }

    /**
     * The user id in whichever context has a session, manager first.
     *
     * The manager is checked first because a visitor holding both sessions is an
     * administrator looking at the manager, and that is the account the
     * identities widget should be operating on.
     */
    public static function anyCurrentUserId(): ?int
    {
        return static::currentUserId(Config::CONTEXT_MGR)
            ?? static::currentUserId(Config::CONTEXT_WEB);
    }

    /**
     * The context the current session belongs to, or null when signed out.
     */
    public static function currentContext(): ?string
    {
        if (static::currentUserId(Config::CONTEXT_MGR) !== null) {
            return Config::CONTEXT_MGR;
        }

        if (static::currentUserId(Config::CONTEXT_WEB) !== null) {
            return Config::CONTEXT_WEB;
        }

        return null;
    }

    /**
     * Every provider identity linked to a user.
     *
     * @return Collection<int, SocialAccount>
     */
    public static function linkedAccounts(int $userId): Collection
    {
        try {
            return SocialAccount::forUser($userId);
        } catch (\Throwable $e) {
            Log::warning('Failed to load linked social accounts: ' . $e->getMessage());

            return new Collection();
        }
    }

    /**
     * The slugs a user has already linked, for hiding "connect" buttons that
     * would only lead to "already linked".
     *
     * @return string[]
     */
    public static function linkedSlugs(int $userId): array
    {
        return static::linkedAccounts($userId)
            ->map(static fn (SocialAccount $account) => $account->provider?->slug)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Providers on offer that this user has not linked yet.
     *
     * @return Collection<int, RegisteredProvider>
     */
    public static function connectableProviders(int $userId): Collection
    {
        $linked = static::linkedSlugs($userId);

        return ProviderRegistry::enabled()->reject(
            static fn (RegisteredProvider $provider) => in_array($provider->slug, $linked, true)
        )->values();
    }

    /**
     * Whether removing this identity would leave the user with none.
     */
    public static function isLastIdentity(int $userId): bool
    {
        try {
            return SocialAccount::countForUser($userId) <= 1;
        } catch (\Throwable $e) {
            // If we cannot tell, assume it is the last one: refusing to unlink is
            // recoverable, locking someone out of their own account is not.
            return true;
        }
    }
}
