<?php

namespace Elcreator\aSocialAuth\Support;

/**
 * What the package needs to remember across the trip to the provider and back.
 *
 * The callback arrives as a bare GET from a third party: it carries an OAuth
 * code and nothing else. Everything the request that started the flow knew — was
 * this a sign-in or an attempt to add a second identity, which context, where
 * the visitor should end up, who they already were — has to be parked somewhere
 * for the few seconds the redirect takes. That is this class.
 *
 * State lives in `$_SESSION`, the same store Evolution CMS keeps its own login
 * flags in and the one HybridAuth uses for its OAuth `state` parameter. It is
 * single-slot on purpose: a visitor has one social flow in progress at a time,
 * and a stale entry from an abandoned attempt must not be able to influence the
 * next one, so starting a flow overwrites whatever was there.
 */
class FlowState
{
    public const KEY = 'asocialauth.flow';

    public const INTENT_LOGIN = 'login';
    public const INTENT_LINK  = 'link';

    /**
     * How long a started flow stays valid. A real OAuth round trip takes
     * seconds; anything much older is an abandoned tab, and honouring it would
     * mean acting on an intent the visitor has long forgotten.
     */
    public const TTL_SECONDS = 900;

    /**
     * Record the intent of a flow that is about to leave for the provider.
     */
    public static function start(
        string $providerSlug,
        string $intent,
        string $context,
        ?string $returnPath = null,
        ?int $userId = null
    ): void {
        static::ensureSession();

        $_SESSION[static::KEY] = [
            'provider' => $providerSlug,
            'intent'   => $intent === self::INTENT_LINK ? self::INTENT_LINK : self::INTENT_LOGIN,
            'context'  => $context,
            'return'   => $returnPath,
            'user_id'  => $userId,
            'started'  => time(),
        ];
    }

    /**
     * Read the flow back, verifying it belongs to the provider now calling back
     * and has not expired. Reading consumes it: a callback is a one-shot event,
     * and leaving the state behind would let a replayed URL reuse it.
     *
     * @return array{provider: string, intent: string, context: string, return: ?string, user_id: ?int}|null
     */
    public static function consume(string $providerSlug): ?array
    {
        static::ensureSession();

        $state = $_SESSION[static::KEY] ?? null;
        unset($_SESSION[static::KEY]);

        if (!is_array($state)) {
            return null;
        }

        if (($state['provider'] ?? null) !== $providerSlug) {
            return null;
        }

        if ((time() - (int) ($state['started'] ?? 0)) > static::TTL_SECONDS) {
            return null;
        }

        return [
            'provider' => (string) $state['provider'],
            'intent'   => (string) ($state['intent'] ?? self::INTENT_LOGIN),
            'context'  => (string) ($state['context'] ?? Config::CONTEXT_WEB),
            'return'   => isset($state['return']) ? (string) $state['return'] : null,
            'user_id'  => isset($state['user_id']) ? (int) $state['user_id'] : null,
        ];
    }

    public static function clear(): void
    {
        static::ensureSession();
        unset($_SESSION[static::KEY]);
    }

    /**
     * Queue a message for the page the visitor is about to be redirected to.
     *
     * Not Laravel's flash bag: the manager login page is rendered by Evolution
     * CMS's own theme code, outside the request lifecycle that would replay a
     * Laravel flash, so the message has to sit somewhere both sides can read.
     */
    public static function flashError(string $message): void
    {
        static::ensureSession();
        $_SESSION['asocialauth.error'] = $message;
    }

    public static function flashSuccess(string $message): void
    {
        static::ensureSession();
        $_SESSION['asocialauth.success'] = $message;
    }

    /**
     * Read and clear a queued message.
     */
    public static function takeError(): ?string
    {
        return static::take('asocialauth.error');
    }

    public static function takeSuccess(): ?string
    {
        return static::take('asocialauth.success');
    }

    protected static function take(string $key): ?string
    {
        static::ensureSession();

        $message = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        return is_string($message) && $message !== '' ? $message : null;
    }

    /**
     * $_SESSION is normally already an array by the time anything here runs —
     * Evolution CMS sets it up before routing. This only guards the edge where a
     * route is reached without that bootstrap having happened.
     */
    protected static function ensureSession(): void
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }
    }
}
