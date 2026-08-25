<?php

use Elcreator\aSocialAuth\Support\Config;
use Elcreator\aSocialAuth\Support\FlowState;
use Elcreator\aSocialAuth\Support\Log;
use Elcreator\aSocialAuth\Support\Promotion;
use Elcreator\aSocialAuth\Support\Renderer;
use Elcreator\aSocialAuth\Support\UserResolver;

/**
 * Event listeners. Loaded by the service provider via loadPluginsFrom().
 *
 * The front-end snippets ([[aSocialAuthButtons]], [[aSocialAuthIdentities]]) are
 * registered by the service provider instead, because a snippet has to exist
 * before the parser reaches the tag.
 */

/**
 * Append the sign-in buttons to the manager login form.
 *
 * Everything is wrapped: a failure here would be a failure to render the login
 * page, and that is the one page that must never break — an administrator
 * locked out of the manager cannot switch the extension off from inside it.
 */
Event::listen('evolution.OnManagerLoginFormRender', function () {
    try {
        return Renderer::loginButtons();
    } catch (\Throwable $e) {
        Log::warning('Failed to render social login buttons: ' . $e->getMessage());

        return '';
    }
});

/**
 * Warn when an account with no reachable address is given a manager role.
 *
 * Promotion is the administrator's decision and this does not block it — but an
 * account created through Telegram or Steam carries a synthesised address that
 * can never receive mail, so it has no password-recovery route. Losing the
 * linked provider would mean losing the manager account outright. The warning
 * lands in the event log where an administrator can find it afterwards.
 */
Event::listen('evolution.OnBeforeUserSave', function ($params = []) {
    try {
        $user = is_array($params) ? ($params['user'] ?? []) : [];
        $role = (int) ($user['role'] ?? 0);

        if (!Promotion::isManagerRole($role)) {
            return;
        }

        $email = (string) ($user['email'] ?? '');

        if ($email !== '' && !UserResolver::isPlaceholderEmail($email)) {
            return;
        }

        Log::warning(sprintf(
            'User "%s" is being given role %d but has no reachable e-mail address (%s). '
            . 'Password recovery will not be possible for this account.',
            (string) ($user['username'] ?? '?'),
            $role,
            $email !== '' ? $email : 'none'
        ));
    } catch (\Throwable $e) {
        // A warning must never be the reason a user save fails.
    }
});

if (!function_exists('aSocialAuth_enabled')) {
    /**
     * Whether social auth is switched on. A plain function so template code and
     * other plugins can ask without importing anything.
     */
    function aSocialAuth_enabled(): bool
    {
        return Config::isEnabled();
    }
}

if (!function_exists('aSocialAuth_error')) {
    /**
     * Read and clear the message left behind by a failed attempt.
     */
    function aSocialAuth_error(): ?string
    {
        return FlowState::takeError();
    }
}

if (!function_exists('aSocialAuth_identities')) {
    /**
     * The linked-accounts widget as a string, for templates that build their own
     * profile page in PHP rather than through a snippet tag.
     */
    function aSocialAuth_identities(?string $returnPath = null): string
    {
        try {
            return Renderer::identitiesWidget($returnPath);
        } catch (\Throwable $e) {
            Log::warning('aSocialAuth_identities() failed: ' . $e->getMessage());

            return '';
        }
    }
}
