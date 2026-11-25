<?php

namespace Elcreator\aSocialAuth\Support;

/**
 * Makes sure a visitor who is shown a form can come back with the same session.
 *
 * Up to 3.5.9 Evolution CMS sends a new guest no session cookie with an ordinary
 * document, and then discards the session as unreachable — taking the CSRF token
 * and the nonce we have just rendered with it.
 *
 * The cookie is only noted as wanted while rendering, and sent when a *document*
 * goes out (OnWebPagePrerender). A routed response gets its cookie from the
 * StartSession middleware, and must not carry a second one.
 */
class SessionCookie
{
    protected const WANTED = 'session.cookie.wanted';

    public static function want(): void
    {
        RequestState::set(static::WANTED, true);
    }

    public static function wanted(): bool
    {
        return (bool) RequestState::get(static::WANTED, false);
    }

    /**
     * Called as a document is about to be sent.
     */
    public static function send(): void
    {
        if (!static::wanted() || !class_exists('EvoSessionProxy', false) || headers_sent()) {
            return;
        }

        $name = (string) config('session.cookie', 'evo_session');

        if ($name === '' || isset($_COOKIE[$name]) || Config::sessionsDisabled() || static::alreadySent($name)) {
            return;
        }

        // Not EvoSessionProxy::saveAndEmitCookie(): that closes the session for
        // whatever the rest of the request still wants to write to it.
        $id = (string) app('session')->driver()->getId();

        if ($id !== '') {
            setcookie($name, $id, static::options((array) config('session', [])));
        }
    }

    /**
     * The flags the core would have used.
     */
    public static function options(array $config): array
    {
        $lifetime = empty($config['expire_on_close']) ? (int) ($config['lifetime'] ?? 0) * 60 : 0;

        return [
            'expires'  => $lifetime > 0 ? time() + $lifetime : 0,
            'path'     => (string) ($config['path'] ?? '/'),
            'domain'   => (string) ($config['domain'] ?? ''),
            'secure'   => (bool) ($config['secure'] ?? str_starts_with(strtolower(Config::getSiteUrl()), 'https://')),
            'httponly' => (bool) ($config['http_only'] ?? true),
            'samesite' => (string) ($config['same_site'] ?? 'Lax'),
        ];
    }

    protected static function alreadySent(string $name): bool
    {
        foreach (headers_list() as $header) {
            if (stripos($header, 'Set-Cookie: ' . $name . '=') === 0) {
                return true;
            }
        }

        return false;
    }
}
