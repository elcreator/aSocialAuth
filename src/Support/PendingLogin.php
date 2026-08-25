<?php

namespace Elcreator\aSocialAuth\Support;

/**
 * A sign-in that got as far as proving who the visitor is, then stopped.
 *
 * Held only while a login pipe's challenge is outstanding. It is not a session
 * and confers nothing: it records a user id, the context the attempt was for,
 * and where to go afterwards. Until the retried pipeline completes, the visitor
 * is signed in to nothing.
 *
 * The window is short on purpose. A pending login is a claim that a provider
 * vouched for this account moments ago; the longer it can sit in a session, the
 * more it starts to resemble a credential of its own.
 */
class PendingLogin
{
    public const KEY = 'asocialauth.pending';

    /** Long enough to type a code out of an authenticator app, no longer. */
    public const TTL_SECONDS = 600;

    public static function store(
        int $userId,
        string $context,
        string $providerSlug,
        ?string $returnPath,
        string $challenge = '',
        array $payload = []
    ): void {
        static::ensureSession();

        $_SESSION[static::KEY] = [
            'user_id'   => $userId,
            'context'   => $context,
            'provider'  => $providerSlug,
            'return'    => $returnPath,
            'challenge' => $challenge,
            'payload'   => $payload,
            'started'   => time(),
        ];
    }

    /**
     * Read the pending login without consuming it — for a challenge page that
     * needs to know whose factor it is checking before the visitor submits.
     *
     * @return array{user_id: int, context: string, provider: string, return: ?string, challenge: string, payload: array}|null
     */
    public static function peek(): ?array
    {
        static::ensureSession();

        $pending = $_SESSION[static::KEY] ?? null;

        if (!is_array($pending)) {
            return null;
        }

        if ((time() - (int) ($pending['started'] ?? 0)) > static::TTL_SECONDS) {
            static::clear();

            return null;
        }

        return [
            'user_id'   => (int) ($pending['user_id'] ?? 0),
            'context'   => (string) ($pending['context'] ?? Config::CONTEXT_WEB),
            'provider'  => (string) ($pending['provider'] ?? ''),
            'return'    => isset($pending['return']) ? (string) $pending['return'] : null,
            'challenge' => (string) ($pending['challenge'] ?? ''),
            'payload'   => (array) ($pending['payload'] ?? []),
        ];
    }

    /**
     * Read and remove it. A pending login is redeemable once.
     */
    public static function consume(): ?array
    {
        $pending = static::peek();
        static::clear();

        return $pending;
    }

    public static function exists(): bool
    {
        return static::peek() !== null;
    }

    public static function clear(): void
    {
        static::ensureSession();
        unset($_SESSION[static::KEY]);
    }

    protected static function ensureSession(): void
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }
    }
}
