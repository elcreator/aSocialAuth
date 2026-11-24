<?php

namespace Elcreator\aSocialAuth\Support;

use Elcreator\aSocialAuth\Exceptions\SocialAuthException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Hybridauth\HttpClient\Curl;
use Hybridauth\User\Profile;

/**
 * Turns an OpenID Connect ID token handed over by the browser into a profile.
 *
 * Unlike the OAuth legs, nothing here came from the provider directly: the token
 * passed through the visitor's browser, so every claim is worthless until the
 * signature, issuer, audience, expiry and nonce have all been checked.
 */
class IdTokenVerifier
{
    /** Pinned: the token must never get to choose its own algorithm. */
    protected const ALGORITHM = 'RS256';

    protected const CLOCK_SKEW = 60;

    protected const CACHE_KEY = 'asocialauth.jwks.';

    /** Bounds for the provider's own Cache-Control, and the floor between refetches. */
    protected const MIN_TTL           = 300;
    protected const MAX_TTL           = 86400;
    protected const REFETCH_AFTER     = 60;

    /**
     * Replaces the HTTP call that loads the key set. For tests.
     *
     * @var (callable(string): array{body: string, max_age: ?int})|null
     */
    public static $fetcher = null;

    /**
     * @param array{issuers: string[], jwks: string} $spec from SocialProvider::oneTap()
     * @throws SocialAuthException when the token cannot be trusted
     */
    public static function verify(string $token, array $spec, string $audience, ?string $nonce): Profile
    {
        if ($nonce === null || $nonce === '') {
            throw SocialAuthException::lexicon('error_expired');
        }

        try {
            $claims = static::decode($token, $spec['jwks']);
            static::assertClaims($claims, $spec['issuers'], $audience, $nonce);
        } catch (\Throwable $e) {
            Log::warning('ID token rejected: ' . $e->getMessage());

            throw SocialAuthException::lexicon('error_oauth');
        }

        return static::profile($claims);
    }

    protected static function decode(string $token, string $jwksUrl): array
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            throw new \UnexpectedValueException('not a JWT');
        }

        $header = json_decode(JWT::urlsafeB64Decode($segments[0]), true);
        $kid    = is_array($header) && is_string($header['kid'] ?? null) ? $header['kid'] : '';

        if ($kid === '') {
            throw new \UnexpectedValueException('no key id');
        }

        $keys = JWK::parseKeySet(static::keySet($jwksUrl, $kid), static::ALGORITHM);

        // The leeway is a global of the library; another package may rely on its own.
        $leeway      = JWT::$leeway;
        JWT::$leeway = static::CLOCK_SKEW;

        try {
            return (array) JWT::decode($token, $keys);
        } finally {
            JWT::$leeway = $leeway;
        }
    }

    protected static function assertClaims(array $claims, array $issuers, string $audience, string $nonce): void
    {
        if (!in_array($claims['iss'] ?? null, $issuers, true)) {
            throw new \UnexpectedValueException('unexpected issuer');
        }

        if ($audience === '' || ($claims['aud'] ?? null) !== $audience) {
            throw new \UnexpectedValueException('issued for a different client');
        }

        // The library only checks exp when it is there.
        if (!isset($claims['exp'])) {
            throw new \UnexpectedValueException('no expiry');
        }

        if (!is_string($claims['nonce'] ?? null) || !hash_equals($nonce, $claims['nonce'])) {
            throw new \UnexpectedValueException('nonce mismatch');
        }

        if (!is_string($claims['sub'] ?? null) || $claims['sub'] === '') {
            throw new \UnexpectedValueException('no subject');
        }
    }

    /**
     * The same mapping HybridAuth applies to the userinfo response, so an
     * identity looks the same whichever way it arrived.
     */
    protected static function profile(array $claims): Profile
    {
        $text = static fn (string $key): string => is_string($claims[$key] ?? null) ? $claims[$key] : '';

        $profile              = new Profile();
        $profile->identifier  = $claims['sub'];
        $profile->firstName   = $text('given_name');
        $profile->lastName    = $text('family_name');
        $profile->displayName = $text('name');
        $profile->photoURL    = $text('picture');
        $profile->language    = $text('locale');
        $profile->email       = $text('email');

        $profile->emailVerified = in_array($claims['email_verified'] ?? false, [true, 'true'], true)
            ? $profile->email
            : '';

        return $profile;
    }

    /**
     * The provider's signing keys, cached for as long as the provider allows.
     *
     * An unknown key id normally means the keys were rotated, so it triggers one
     * refetch — rate limited, because the id is chosen by whoever sent the token.
     */
    protected static function keySet(string $url, string $kid): array
    {
        $cacheKey = static::CACHE_KEY . md5($url);
        $cached   = static::cacheGet($cacheKey);

        if ($cached !== null) {
            $known = in_array($kid, array_column($cached['set']['keys'], 'kid'), true);

            if ($known || time() - $cached['fetched'] < static::REFETCH_AFTER) {
                return $cached['set'];
            }
        }

        $response = static::$fetcher !== null ? (static::$fetcher)($url) : static::fetch($url);
        $set      = json_decode($response['body'], true);

        if (!is_array($set) || !is_array($set['keys'] ?? null) || $set['keys'] === []) {
            throw new \RuntimeException('could not read the provider key set');
        }

        $ttl = max(static::MIN_TTL, min(static::MAX_TTL, (int) ($response['max_age'] ?? 0)));

        static::cachePut($cacheKey, ['set' => $set, 'fetched' => time()], $ttl);

        return $set;
    }

    /**
     * @return array{body: string, max_age: ?int}
     */
    protected static function fetch(string $url): array
    {
        $client = new Curl();
        $client->setCurlOptions([CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 10]);

        $body = $client->request($url);

        if ((int) $client->getResponseHttpCode() !== 200 || !is_string($body)) {
            throw new \RuntimeException(
                'key set request failed: HTTP ' . $client->getResponseHttpCode() . ' ' . $client->getResponseClientError()
            );
        }

        $cacheControl = (string) ($client->getResponseHeader()['cache_control'] ?? '');

        return [
            'body'    => $body,
            'max_age' => preg_match('/max-age=(\d+)/i', $cacheControl, $m) ? (int) $m[1] : null,
        ];
    }

    protected static function cacheGet(string $key): ?array
    {
        try {
            $value = app('cache.store')->get($key);
        } catch (\Throwable $e) {
            return null;
        }

        return is_array($value) && is_array($value['set']['keys'] ?? null) && isset($value['fetched'])
            ? $value
            : null;
    }

    protected static function cachePut(string $key, array $value, int $ttl): void
    {
        try {
            app('cache.store')->put($key, $value, $ttl);
        } catch (\Throwable $e) {
            // Uncached keys only cost a request per sign-in.
        }
    }
}
