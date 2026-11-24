<?php

namespace Elcreator\aSocialAuth\Support;

use Composer\InstalledVersions;
use Firebase\JWT\JWK;

/**
 * Features that need a Composer package the site may not have.
 *
 * They are `suggest`ed rather than required, with the unusable versions under
 * `conflict`: a site that never turns the feature on should not carry the
 * package, and one that does installs it through the CMS's own
 * `package:installrequire`.
 */
class Packages
{
    public const ONE_TAP = 'onetap';

    /** @var array<string, array{label: string, packages: string[], probe: class-string}> */
    protected const FEATURES = [
        self::ONE_TAP => [
            'label'    => 'Google One Tap',
            'packages' => ['firebase/php-jwt'],
            'probe'    => JWK::class,
        ],
    ];

    /** @var array<string, bool> Answers for tests, by feature. */
    public static array $fake = [];

    /** @return string[] */
    public static function features(): array
    {
        return array_keys(static::FEATURES);
    }

    public static function has(string $feature): bool
    {
        return isset(static::FEATURES[$feature]);
    }

    public static function label(string $feature): string
    {
        return static::FEATURES[$feature]['label'] ?? $feature;
    }

    /** @return string[] */
    public static function packages(string $feature): array
    {
        return static::FEATURES[$feature]['packages'] ?? [];
    }

    public static function available(string $feature): bool
    {
        if (isset(static::$fake[$feature])) {
            return static::$fake[$feature];
        }

        return static::has($feature) && class_exists(static::FEATURES[$feature]['probe']);
    }

    /**
     * The packages Composer has not installed.
     *
     * @return string[]
     */
    public static function missing(string $feature): array
    {
        if (static::available($feature)) {
            return [];
        }

        if (!class_exists(InstalledVersions::class)) {
            return static::packages($feature);
        }

        return array_values(array_filter(
            static::packages($feature),
            static fn (string $package): bool => !InstalledVersions::isInstalled($package)
        ));
    }

    public static function installCommand(string $feature): string
    {
        return 'cd core && php artisan asocialauth:packages --install=' . $feature;
    }

    /**
     * Tell the administrator, once a day: this is reached on every page view.
     */
    public static function warnMissing(string $feature): void
    {
        try {
            if (!app('cache.store')->add('asocialauth.packages.warned.' . $feature, 1, 86400)) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        Log::warning(sprintf(
            '%s is switched on but needs %s. Install it with: %s',
            static::label($feature),
            implode(', ', static::packages($feature)),
            static::installCommand($feature)
        ));
    }
}
