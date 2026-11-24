<?php

namespace Elcreator\aSocialAuth\Support;

use Elcreator\aSocialAuth\Enums\SocialProvider;
use Elcreator\aSocialAuth\Models\RegisteredProvider;

/**
 * One Tap: the provider's own prompt, which signs a returning visitor in with
 * one click, or with none when automatic sign-in is on.
 *
 * Only ever opens a *web* session. A prompt that can complete without a click
 * must not be able to hand out `mgrValidated`, so under `context: mgr` the whole
 * feature is off.
 */
class OneTap
{
    public const NONCE_KEY    = 'asocialauth_onetap_nonce';
    public const SUPPRESS_KEY = 'asocialauth_onetap_suppressed';

    /** Set on logout, so signing out is not undone by the next page view. */
    public const SIGNED_OUT_COOKIE = 'asocialauth_signed_out';

    /** After a failure the prompt stays away, or automatic sign-in would loop. */
    public const SUPPRESS_SECONDS = 600;

    protected const SIGNED_OUT_SECONDS = 2592000;

    /** One prompt per page, however many widgets are on it. */
    protected const EMITTED = 'onetap.emitted';

    /**
     * The first enabled provider that has One Tap and is configured to use it.
     */
    public static function provider(): ?RegisteredProvider
    {
        if (!static::contextAllows()) {
            return null;
        }

        return ProviderRegistry::enabled()->first(
            static fn (RegisteredProvider $provider) => static::offeredBy($provider)
        );
    }

    public static function offeredBy(RegisteredProvider $provider): bool
    {
        if (!static::contextAllows()
            || !Config::oneTapEnabled($provider->slug)
            || static::spec($provider) === null) {
            return false;
        }

        if (!Packages::available(Packages::ONE_TAP)) {
            Packages::warnMissing(Packages::ONE_TAP);

            return false;
        }

        return true;
    }

    /**
     * @return array{script: string, issuers: string[], jwks: string}|null
     */
    public static function spec(RegisteredProvider $provider): ?array
    {
        return SocialProvider::fromSlug($provider->slug)?->oneTap();
    }

    public static function clientId(RegisteredProvider $provider): string
    {
        return trim((string) (ProviderRegistry::credentials($provider->slug)['id'] ?? ''));
    }

    /**
     * Everything the prompt partial needs, or null when there is nothing to show.
     */
    public static function viewData(?string $returnPath = null): ?array
    {
        if (RequestState::get(static::EMITTED) || !Config::isEnabled() || !Config::routesEnabled() || !static::visitorMayBePrompted()) {
            return null;
        }

        $provider = static::provider();

        if ($provider === null) {
            return null;
        }

        RequestState::set(static::EMITTED, true);

        $signedOut = static::hasSignedOut();

        return [
            'script'     => static::spec($provider)['script'],
            'clientId'   => static::clientId($provider),
            'nonce'      => static::nonce(),
            'autoSelect' => Config::oneTapAutoSelect($provider->slug) && !$signedOut,
            'signedOut'  => $signedOut,
            'action'     => Config::buildOneTapUrl($provider->slug),
            'token'      => csrf_token(),
            'returnName' => Config::returnParam(),
            'returnPath' => $returnPath,
        ];
    }

    /**
     * Not while anyone is signed in, half-way through a challenge, or has just
     * been refused.
     */
    public static function visitorMayBePrompted(): bool
    {
        return Identity::anyCurrentUserId() === null
            && !PendingLogin::exists()
            && (int) ($_SESSION[static::SUPPRESS_KEY] ?? 0) < time();
    }

    public static function suppress(): void
    {
        static::ensureSession();
        $_SESSION[static::SUPPRESS_KEY] = time() + static::SUPPRESS_SECONDS;
    }

    /**
     * Binds the ID token to this session. Stable until spent, so several open
     * tabs do not invalidate one another.
     */
    public static function nonce(): string
    {
        static::ensureSession();

        $nonce = $_SESSION[static::NONCE_KEY] ?? null;

        if (!is_string($nonce) || $nonce === '') {
            $nonce = $_SESSION[static::NONCE_KEY] = bin2hex(random_bytes(16));
        }

        return $nonce;
    }

    /**
     * Read and spend the nonce, so a token is redeemable once.
     */
    public static function consumeNonce(): ?string
    {
        static::ensureSession();

        $nonce = $_SESSION[static::NONCE_KEY] ?? null;
        unset($_SESSION[static::NONCE_KEY]);

        return is_string($nonce) && $nonce !== '' ? $nonce : null;
    }

    // ------------------------------------------------------------------
    // Sign-out
    // ------------------------------------------------------------------

    /**
     * Logout destroys the session, so the fact has to outlive it in a cookie.
     */
    public static function rememberSignOut(): void
    {
        static::writeCookie('1', time() + static::SIGNED_OUT_SECONDS);
        $_COOKIE[static::SIGNED_OUT_COOKIE] = '1';
    }

    public static function forgetSignOut(): void
    {
        if (!static::hasSignedOut()) {
            return;
        }

        static::writeCookie('', time() - 3600);
        unset($_COOKIE[static::SIGNED_OUT_COOKIE]);
    }

    public static function hasSignedOut(): bool
    {
        return !empty($_COOKIE[static::SIGNED_OUT_COOKIE]);
    }

    /**
     * Reset the once-per-request guard. For tests.
     */
    public static function reset(): void
    {
        RequestState::forget(static::EMITTED);
    }

    protected static function contextAllows(): bool
    {
        return Config::configuredContext() !== Config::CONTEXT_MGR;
    }

    protected static function writeCookie(string $value, int $expires): void
    {
        if (headers_sent()) {
            return;
        }

        $site = parse_url(Config::getSiteUrl());

        setcookie(static::SIGNED_OUT_COOKIE, $value, [
            'expires'  => $expires,
            'path'     => rtrim((string) ($site['path'] ?? ''), '/') . '/',
            'secure'   => ($site['scheme'] ?? '') === 'https',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    protected static function ensureSession(): void
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }
    }
}
