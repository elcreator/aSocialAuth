<?php

namespace Elcreator\aSocialAuth\Support;

/**
 * Typed access to `cms.settings.aSocialAuth`, and the single place URLs are built.
 *
 * Every route the package answers on, and every callback URL handed to a
 * provider, is derived here from `routes.prefix` and `routes.patterns`. That is
 * what makes the prefix genuinely configurable: nothing anywhere else spells
 * "asocialauth", so changing one config value moves the whole surface.
 */
class Config
{
    /** Contexts a social login may open a session in. */
    public const CONTEXT_MGR = 'mgr';
    public const CONTEXT_WEB = 'web';
    public const CONTEXT_AUTO = 'auto';

    public static function isEnabled(): bool
    {
        return (bool) static::get('enable', false);
    }

    /**
     * Read one dotted key out of the package config.
     */
    public static function get(string $key, $default = null)
    {
        return config('cms.settings.aSocialAuth.' . $key, $default);
    }

    // ------------------------------------------------------------------
    // Context
    // ------------------------------------------------------------------

    /**
     * The configured context, one of mgr|web|auto.
     */
    public static function configuredContext(): string
    {
        $context = strtolower(trim((string) static::get('context', self::CONTEXT_MGR)));

        return in_array($context, [self::CONTEXT_MGR, self::CONTEXT_WEB, self::CONTEXT_AUTO], true)
            ? $context
            : self::CONTEXT_MGR;
    }

    /**
     * The context a flow starting right now should open a session in.
     *
     * Under 'auto' this is decided by where the visitor came from. The OAuth
     * routes are front-end routes even when the button was on the manager login
     * page, so "am I in the manager?" cannot be asked of the current request —
     * the referring page is the only evidence available at that moment, and the
     * answer is captured into the session before the provider round trip.
     */
    public static function resolveContext(?string $referer = null): string
    {
        $configured = static::configuredContext();

        if ($configured !== self::CONTEXT_AUTO) {
            return $configured;
        }

        $referer ??= (string) ($_SERVER['HTTP_REFERER'] ?? '');

        return static::refererIsManager($referer) ? self::CONTEXT_MGR : self::CONTEXT_WEB;
    }

    /**
     * Whether a referring URL points into this site's manager.
     *
     * The host is checked, not just the path, and that is the whole point. A
     * Referer header is chosen by whoever wrote the page the visitor clicked
     * from, so anyone can send a visitor here from `https://evil.test/manager/`.
     * Matching on the path alone would let that page decide that the resulting
     * social login should open a *manager* session rather than a web one —
     * handing an ordinary member `mgrValidated`, which the core reads as "this
     * is a backend user" in places well beyond this package: the site-offline
     * bypass, the debug-output guard in the exception handler, and the
     * document-group merge in getUserDocGroups().
     *
     * So a referer only counts when it is genuinely ours.
     */
    public static function refererIsManager(string $referer): bool
    {
        if ($referer === '') {
            return false;
        }

        $parts = parse_url($referer);

        if ($parts === false || !isset($parts['path'])) {
            return false;
        }

        if (!static::isOwnHost($parts)) {
            return false;
        }

        $path = (string) $parts['path'];

        if ($path === '') {
            return false;
        }

        $managerPath = (string) parse_url(static::getManagerUrl(), PHP_URL_PATH);
        $managerPath = '/' . trim($managerPath, '/');

        if ($managerPath === '/') {
            $managerPath = '/manager';
        }

        return str_starts_with(rtrim($path, '/') . '/', rtrim($managerPath, '/') . '/');
    }

    /**
     * The path and query of a Referer, but only when it is genuinely ours.
     *
     * Used as the "go back where you were" destination after connecting or
     * disconnecting an identity. The resulting redirect is same-site either way,
     * so a foreign referer is not dangerous here — but letting one choose the
     * landing page is still someone else deciding where our flow ends, and the
     * host check costs nothing.
     */
    public static function refererPath(string $referer): ?string
    {
        if ($referer === '') {
            return null;
        }

        $parts = parse_url($referer);

        if ($parts === false || !static::isOwnHost($parts)) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '');

        if ($path === '') {
            return null;
        }

        $query = (string) ($parts['query'] ?? '');

        return static::sanitizeReturnPath($path . ($query !== '' ? '?' . $query : ''));
    }

    /**
     * Whether a parsed URL belongs to this site.
     *
     * A referer with no host is same-origin by definition — browsers send a full
     * URL, but a relative value can reach us in tests and through proxies.
     *
     * @param array $parts output of parse_url()
     */
    protected static function isOwnHost(array $parts): bool
    {
        if (!isset($parts['host']) || $parts['host'] === '') {
            return true;
        }

        $siteHost = (string) parse_url(static::getSiteUrl(), PHP_URL_HOST);

        if ($siteHost === '') {
            $siteHost = (string) ($_SERVER['HTTP_HOST'] ?? '');
            $siteHost = (string) parse_url('http://' . $siteHost, PHP_URL_HOST);
        }

        return $siteHost !== '' && strcasecmp((string) $parts['host'], $siteHost) === 0;
    }

    // ------------------------------------------------------------------
    // Routes
    // ------------------------------------------------------------------

    public static function routesEnabled(): bool
    {
        return (bool) static::get('routes.enable', true);
    }

    /**
     * The URL prefix every route sits under, normalised without slashes.
     *
     * `route_prefix` at the top level is still honoured: it is where the setting
     * lived before the routes block existed, and an install that published the
     * old config should not lose its URLs to an upgrade.
     */
    public static function getRoutePrefix(): string
    {
        $prefix = static::get('routes.prefix');

        if ($prefix === null || trim((string) $prefix) === '') {
            $prefix = static::get('route_prefix', 'asocialauth');
        }

        return trim((string) $prefix, '/');
    }

    /**
     * One route pattern — 'login', 'callback', 'link' or 'unlink' — relative to
     * the prefix, with {provider} still in place for the router to bind.
     */
    public static function routePattern(string $name): string
    {
        $defaults = [
            'login'    => '{provider}',
            'callback' => '{provider}/callback',
            'link'     => '{provider}/link',
            'unlink'   => '{provider}/unlink',

            'credentials_login'    => 'credentials/login',
            'credentials_register' => 'credentials/register',
            'credentials_recover'  => 'credentials/recover',
            'credentials_reset'    => 'credentials/reset',
            'credentials_email'    => 'credentials/email',
            'credentials_verify'   => 'credentials/verify',
        ];

        $pattern = static::get('routes.patterns.' . $name, $defaults[$name] ?? null);
        $pattern = trim((string) $pattern, '/');

        if ($pattern === '') {
            $pattern = $defaults[$name] ?? '';
        }

        return $pattern;
    }

    public static function routeName(string $name): string
    {
        return ((string) static::get('routes.name_prefix', 'asocialauth.')) . $name;
    }

    public static function providerPattern(): string
    {
        $pattern = (string) static::get('routes.provider_pattern', '[a-z0-9]+');

        return $pattern !== '' ? $pattern : '[a-z0-9]+';
    }

    /**
     * Middleware for the route group. Null in config means the application's
     * global stack, which is what an unauthenticated front-end route needs.
     */
    public static function routeMiddleware(): array
    {
        $middleware = static::get('routes.middleware');

        if (is_array($middleware)) {
            return $middleware;
        }

        return (array) config('app.middleware.global', []);
    }

    /**
     * An absolute URL for one of the package's routes with {provider} filled in.
     */
    public static function routeUrl(string $name, string $providerSlug): string
    {
        $path = static::routePattern($name);
        $path = str_replace('{provider}', rawurlencode($providerSlug), $path);

        $prefix = static::getRoutePrefix();
        $path   = $prefix !== '' ? $prefix . '/' . $path : $path;

        return rtrim(static::getSiteUrl(), '/') . '/' . ltrim($path, '/');
    }

    public static function buildLoginUrl(string $providerSlug): string
    {
        return static::routeUrl('login', $providerSlug);
    }

    /**
     * The redirect URI to register in the provider's developer console.
     */
    public static function buildCallbackUrl(string $providerSlug): string
    {
        return static::routeUrl('callback', $providerSlug);
    }

    public static function buildLinkUrl(string $providerSlug): string
    {
        return static::routeUrl('link', $providerSlug);
    }

    public static function buildUnlinkUrl(string $providerSlug): string
    {
        return static::routeUrl('unlink', $providerSlug);
    }

    /**
     * The action URL for one of the credential forms.
     *
     * @param string $action login|register|recover|reset
     */
    public static function credentialsUrl(string $action): string
    {
        $path   = static::routePattern('credentials_' . $action);
        $prefix = static::getRoutePrefix();
        $path   = $prefix !== '' ? $prefix . '/' . $path : $path;

        return rtrim(static::getSiteUrl(), '/') . '/' . ltrim($path, '/');
    }

    // ------------------------------------------------------------------
    // Redirects
    // ------------------------------------------------------------------

    public static function returnParam(): string
    {
        $param = (string) static::get('redirects.return_param', 'return');

        return $param !== '' ? $param : 'return';
    }

    /**
     * Where a successful login in the given context should land.
     */
    public static function loginRedirect(string $context): string
    {
        $path = (string) static::get('redirects.' . $context, '');

        return static::absoluteFor($context, $path);
    }

    /**
     * Where the identities widget returns to after linking or unlinking.
     */
    public static function linkRedirect(string $context, ?string $fallback = null): string
    {
        $path = (string) static::get('redirects.link', '');

        if ($path === '' && $fallback !== null && $fallback !== '') {
            return $fallback;
        }

        return static::absoluteFor($context, $path);
    }

    /**
     * Where a failed attempt lands. Defaults to the context's own root, which is
     * the manager login page for 'mgr' and the site root for 'web'.
     */
    public static function errorRedirect(string $context): string
    {
        $path = (string) static::get('redirects.error', '');

        return static::absoluteFor($context, $path);
    }

    /**
     * Resolve a configured relative path against the right root for a context.
     */
    public static function absoluteFor(string $context, string $path): string
    {
        $path = trim($path);

        if ($path === '' || $path === '/') {
            return $context === self::CONTEXT_MGR
                ? static::getManagerUrl()
                : rtrim(static::getSiteUrl(), '/') . '/';
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $root = $context === self::CONTEXT_MGR ? static::getManagerUrl() : static::getSiteUrl();

        return rtrim($root, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Sanitise a caller-supplied return path.
     *
     * Only a same-site absolute path survives. An absolute URL, a
     * protocol-relative one, a backslash (which some browsers normalise to a
     * slash) or a control character is discarded — otherwise the parameter would
     * be an open redirect, and a link that starts on your login page and ends on
     * someone else's is exactly the shape a phishing campaign wants.
     */
    public static function sanitizeReturnPath($path): ?string
    {
        if (!is_string($path) || $path === '') {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $path)) {
            return null;
        }

        if (!str_starts_with($path, '/')) {
            return null;
        }

        if (str_starts_with($path, '//') || str_contains($path, '\\')) {
            return null;
        }

        return $path;
    }

    // ------------------------------------------------------------------
    // Appearance
    // ------------------------------------------------------------------

    public static function inlineStyles(): bool
    {
        return (bool) static::get('ui.inline_styles', true);
    }

    /**
     * Default presentation for the front-end widget: 'inline' or 'modal'.
     */
    public static function widgetMode(): string
    {
        return static::normaliseMode(static::get('ui.mode', 'inline'));
    }

    public static function normaliseMode($mode): string
    {
        $mode = strtolower(trim((string) $mode));

        return $mode === 'modal' ? 'modal' : 'inline';
    }

    public static function wrapperClass(): string
    {
        return trim((string) static::get('ui.wrapper_class', ''));
    }

    /**
     * The view backing one of the three surfaces, so a site can swap in its own
     * without publishing and editing the packaged one.
     */
    public static function view(string $surface): string
    {
        $defaults = [
            'login'         => 'aSocialAuth::widget.login',
            'identities'    => 'aSocialAuth::widget.identities',
            'manager_login' => 'aSocialAuth::manager.login-buttons',
        ];

        $view = trim((string) static::get('ui.views.' . $surface, ''));

        return $view !== '' ? $view : ($defaults[$surface] ?? $defaults['login']);
    }

    // ------------------------------------------------------------------
    // E-mail and password
    // ------------------------------------------------------------------

    public static function credentialsLoginEnabled(): bool
    {
        return (bool) static::get('credentials.login', true);
    }

    public static function credentialsRegistrationEnabled(): bool
    {
        return (bool) static::get('credentials.register', false);
    }

    public static function credentialsRecoveryEnabled(): bool
    {
        return (bool) static::get('credentials.recover', true);
    }

    public static function loginAfterRegister(): bool
    {
        return (bool) static::get('credentials.login_after_register', true);
    }

    /**
     * What the sign-in form asks for: 'email', 'username' or 'both'.
     */
    public static function loginField(): string
    {
        $field = strtolower(trim((string) static::get('credentials.login_field', 'both')));

        return in_array($field, ['email', 'username', 'both'], true) ? $field : 'both';
    }

    public static function minimumPasswordLength(): int
    {
        $minimum = (int) static::get('credentials.min_password_length', 6);

        // The core's own validator enforces six; a lower setting here would only
        // produce a confusing rejection further down.
        return max(6, $minimum);
    }

    public static function resetTokenParam(): string
    {
        $param = trim((string) static::get('credentials.reset_token_param', 'hash'));

        return $param !== '' ? $param : 'hash';
    }

    public static function resetPath(): string
    {
        return trim((string) static::get('credentials.reset_path', ''));
    }

    /**
     * The absolute reset link mailed to a visitor, or '' when the site has not
     * nominated a page to host the form.
     */
    public static function resetUrl(string $context, string $token): string
    {
        $path = static::resetPath();

        if ($path === '') {
            return '';
        }

        $url = static::absoluteFor($context, $path);
        $sep = str_contains($url, '?') ? '&' : '?';

        return $url . $sep . static::resetTokenParam() . '=' . rawurlencode($token);
    }

    // ------------------------------------------------------------------
    // E-mail verification
    // ------------------------------------------------------------------

    public static function emailVerificationEnabled(): bool
    {
        return (bool) static::get('credentials.verify_email', true);
    }

    /**
     * How long a claimed address stays claimable, in minutes.
     */
    public static function emailVerificationTtl(): int
    {
        $ttl = (int) static::get('credentials.verify_ttl', 1440);

        return $ttl > 0 ? $ttl : 1440;
    }

    /**
     * The confirmation link mailed to a claimed address.
     *
     * Unlike the password reset, this needs no page on the site: the route
     * handles the token and redirects, so there is nothing for a template author
     * to place and nothing to configure before it works.
     */
    public static function verifyUrl(string $token): string
    {
        $path   = static::routePattern('credentials_verify');
        $prefix = static::getRoutePrefix();
        $path   = $prefix !== '' ? $prefix . '/' . $path : $path;

        return rtrim(static::getSiteUrl(), '/') . '/' . ltrim($path, '/')
            . '?token=' . rawurlencode($token);
    }

    /**
     * Where the confirmation link lands the visitor afterwards.
     */
    public static function verifyRedirect(string $context): string
    {
        return static::absoluteFor($context, (string) static::get('credentials.verify_redirect', ''));
    }

    // ------------------------------------------------------------------
    // Linking and registration
    // ------------------------------------------------------------------

    public static function linkingEnabled(): bool
    {
        return (bool) static::get('linking.enable', true);
    }

    public static function matchByEmail(): bool
    {
        return (bool) static::get('linking.match_by_email', true);
    }

    public static function requireVerifiedEmail(): bool
    {
        return (bool) static::get('linking.require_verified_email', true);
    }

    /**
     * Whether the local account must also have proven the address before a
     * provider identity may be matched onto it.
     */
    public static function requireLocalVerifiedEmail(): bool
    {
        return (bool) static::get('linking.require_local_verified_email', true);
    }

    public static function allowUnlinkLast(): bool
    {
        return (bool) static::get('linking.allow_unlink_last', false);
    }

    public static function shouldCreateUsers(): bool
    {
        return (bool) static::get('registration.enable', static::get('create_users', false));
    }

    public static function defaultRole(): int
    {
        return (int) static::get('registration.role', static::get('default_role', 0));
    }

    /**
     * @return int[]
     */
    public static function defaultGroups(): array
    {
        return array_map('intval', (array) static::get('registration.groups', []));
    }

    public static function placeholderEmailDomain(): string
    {
        $domain = trim((string) static::get('registration.placeholder_email_domain', 'social.invalid'));

        return $domain !== '' ? $domain : 'social.invalid';
    }

    public static function usernamePrefix(): string
    {
        return (string) static::get('registration.username_prefix', '');
    }

    // ------------------------------------------------------------------
    // Models
    // ------------------------------------------------------------------

    public static function getSocialAccountModel(): string
    {
        return (string) static::get('models.social_account', \Elcreator\aSocialAuth\Models\SocialAccount::class);
    }

    public static function getRegisteredProviderModel(): string
    {
        return (string) static::get('models.registered_provider', \Elcreator\aSocialAuth\Models\RegisteredProvider::class);
    }

    public static function getUserModel(): string
    {
        return (string) static::get('models.user', \EvolutionCMS\Models\User::class);
    }

    // ------------------------------------------------------------------
    // URLs
    // ------------------------------------------------------------------

    public static function getSiteUrl(): string
    {
        if (function_exists('evo')) {
            $url = evo()->getConfig('site_url');

            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        $url = config('cms.settings.site_url', '');

        if (is_string($url) && $url !== '') {
            return $url;
        }

        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/';
    }

    public static function getManagerUrl(): string
    {
        if (defined('MODX_MANAGER_URL') && MODX_MANAGER_URL !== '') {
            $managerUrl = (string) MODX_MANAGER_URL;

            if (preg_match('#^https?://#i', $managerUrl)) {
                return rtrim($managerUrl, '/') . '/';
            }

            return rtrim(static::getSiteUrl(), '/') . '/' . ltrim($managerUrl, '/');
        }

        if (function_exists('evo')) {
            $base = evo()->getConfig('base_url');

            if (is_string($base) && $base !== '') {
                return rtrim(static::getSiteUrl(), '/') . '/' . ltrim($base, '/') . 'manager/';
            }
        }

        return rtrim(static::getSiteUrl(), '/') . '/manager/';
    }

    public static function buildManagerUrl(string $path): string
    {
        return rtrim(static::getManagerUrl(), '/') . '/' . ltrim($path, '/');
    }
}
