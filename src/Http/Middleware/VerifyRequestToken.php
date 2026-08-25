<?php

namespace Elcreator\aSocialAuth\Http\Middleware;

use Closure;

/**
 * CSRF verification the package can actually rely on.
 *
 * Evolution CMS ships `EvolutionCMS\Middleware\VerifyCsrfToken`, and applying it
 * looks like enough. It is not, for this package, because of one line in its
 * `requiresVerification()`:
 *
 *     if (empty($_SESSION['mgrValidated'])) {
 *         return false;
 *     }
 *
 * That is a defensible decision for the core, whose state-changing endpoints all
 * live behind a manager session — an anonymous request there carries nothing
 * worth forging. It is the wrong assumption here. This package gives *front-end*
 * users state-changing endpoints, and a front-end user has `webValidated`, never
 * `mgrValidated`. So the core's middleware would wave through every cross-site
 * POST aimed at a signed-in member, while appearing on the route list as if the
 * route were protected.
 *
 * What that would allow, concretely: an attacker's page silently POSTs
 * `credentials/email` on behalf of a logged-in member, claiming an address the
 * attacker owns. The confirmation link arrives in the attacker's mailbox, they
 * follow it, and the victim's account now carries the attacker's e-mail —
 * verified. One password-reset request later the account is theirs. Unlinking a
 * provider and logging a victim into an account of the attacker's choosing are
 * the same class of problem on the same routes.
 *
 * So the check is done here and unconditionally. It reads the same token the
 * core's `csrf_token()` helper writes — `$_SESSION['_token']` — so the forms
 * need no special handling and a manager session is verified exactly as a web
 * one is.
 */
class VerifyRequestToken
{
    /** Header names an XHR may present the token in. */
    protected const HEADERS = ['X-CSRF-TOKEN', 'X-XSRF-TOKEN'];

    public function handle($request, Closure $next)
    {
        if (!$this->tokensMatch($request)) {
            abort(419, 'CSRF token mismatch.');
        }

        return $next($request);
    }

    protected function tokensMatch($request): bool
    {
        $sessionToken = $this->sessionToken();
        $requestToken = $this->requestToken($request);

        if ($sessionToken === '' || $requestToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $requestToken);
    }

    /**
     * The token the core's csrf_token() helper issued for this session.
     *
     * Read directly rather than by calling csrf_token(), because that helper
     * *creates* a token when none exists — which would make an empty session
     * mint a token and then compare it against itself.
     */
    protected function sessionToken(): string
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            return '';
        }

        $token = $_SESSION['_token'] ?? '';

        return is_string($token) ? $token : '';
    }

    protected function requestToken($request): string
    {
        $token = $request->input('_token', '');

        if (!is_scalar($token) || (string) $token === '') {
            foreach (static::HEADERS as $header) {
                $candidate = $request->header($header, '');

                if (is_string($candidate) && $candidate !== '') {
                    return $candidate;
                }
            }

            return '';
        }

        return (string) $token;
    }
}
