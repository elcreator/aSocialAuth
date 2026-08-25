<?php

use Elcreator\aSocialAuth\Http\Middleware\VerifyRequestToken;
use Elcreator\aSocialAuth\Support\Config;
use Illuminate\Http\Request;

/**
 * Two things Evolution CMS does that a package built on it has to work around.
 * Both were live holes in this package before these tests existed.
 */

function runTokenMiddleware(Request $request): string
{
    try {
        (new VerifyRequestToken())->handle($request, fn () => 'passed');

        return 'passed';
    } catch (\Throwable $e) {
        return 'rejected';
    }
}

function postWithToken(?string $token): Request
{
    return Request::create('/asocialauth/credentials/email', 'POST', $token === null ? [] : ['_token' => $token]);
}

describe('CSRF, which the core would skip', function () {
    it('rejects a cross-site POST from a front-end session', function () {
        // The hole this exists to close. EvolutionCMS\Middleware\VerifyCsrfToken
        // returns early when $_SESSION['mgrValidated'] is empty — which is the
        // case for every web user — so applying the core's `csrf` alias here
        // would wave this straight through.
        $_SESSION['_token']       = 'the-real-token';
        $_SESSION['webValidated'] = 1;
        $_SESSION['webInternalKey'] = 42;

        expect(runTokenMiddleware(postWithToken(null)))->toBe('rejected');
        expect(runTokenMiddleware(postWithToken('guessed')))->toBe('rejected');
    });

    it('accepts the token the core helper issued', function () {
        $_SESSION['_token']       = 'the-real-token';
        $_SESSION['webValidated'] = 1;

        expect(runTokenMiddleware(postWithToken('the-real-token')))->toBe('passed');
    });

    it('checks a manager session by exactly the same rule', function () {
        $_SESSION['_token']       = 'the-real-token';
        $_SESSION['mgrValidated'] = 1;

        expect(runTokenMiddleware(postWithToken('nope')))->toBe('rejected');
        expect(runTokenMiddleware(postWithToken('the-real-token')))->toBe('passed');
    });

    it('rejects rather than mints a token when the session has none', function () {
        // csrf_token() creates one on read. Calling it here would make an empty
        // session compare a fresh token against itself and pass.
        unset($_SESSION['_token']);

        expect(runTokenMiddleware(postWithToken('anything')))->toBe('rejected');
        expect($_SESSION['_token'] ?? null)->toBeNull();
    });

    it('rejects an empty token on both sides', function () {
        $_SESSION['_token'] = '';

        expect(runTokenMiddleware(postWithToken('')))->toBe('rejected');
    });

    it('accepts the token from an XHR header', function () {
        $_SESSION['_token'] = 'the-real-token';

        $request = Request::create('/asocialauth/credentials/email', 'POST');
        $request->headers->set('X-CSRF-TOKEN', 'the-real-token');

        expect(runTokenMiddleware($request))->toBe('passed');
    });

    it('survives the session regeneration a login performs', function () {
        // UserLogin destroys the session and copies the data across, so a form
        // rendered before signing in must still submit successfully after.
        $_SESSION['_token'] = 'the-real-token';

        $carried  = $_SESSION;
        $_SESSION = [];
        foreach ($carried as $key => $value) {
            $_SESSION[$key] = $value;
        }

        expect(runTokenMiddleware(postWithToken('the-real-token')))->toBe('passed');
    });
});

describe('context detection, which a Referer can steer', function () {
    beforeEach(function () {
        TestConfig::set('cms.settings.aSocialAuth.context', 'auto');
    });

    it('accepts the manager path on our own host', function () {
        expect(Config::resolveContext('https://example.test/manager/index.php'))->toBe('mgr');
    });

    it('refuses a manager path on somebody else\'s host', function () {
        // The hole: a Referer is chosen by whoever wrote the page the visitor
        // clicked from. Matching on the path alone let any page at
        // evil.test/manager/ decide that this login should open a MANAGER
        // session — handing an ordinary member mgrValidated, which the core
        // reads as "backend user" for the site-offline bypass, the debug-output
        // guard and the document-group merge.
        expect(Config::resolveContext('https://evil.test/manager/'))->toBe('web');
        expect(Config::resolveContext('http://attacker.example/manager/index.php'))->toBe('web');
    });

    it('is not fooled by the host appearing elsewhere in the URL', function () {
        expect(Config::resolveContext('https://evil.test/example.test/manager/'))->toBe('web');
        expect(Config::resolveContext('https://example.test.evil.test/manager/'))->toBe('web');
    });

    it('ignores case in the host, as DNS does', function () {
        expect(Config::resolveContext('https://EXAMPLE.TEST/manager/'))->toBe('mgr');
    });

    it('treats a hostless referer as our own', function () {
        // Not something a browser sends, but proxies and tests do.
        expect(Config::resolveContext('/manager/index.php'))->toBe('mgr');
    });

    it('still refuses a non-manager path on our own host', function () {
        expect(Config::resolveContext('https://example.test/members/login'))->toBe('web');
    });

    it('cannot be steered at all unless the site opted into auto', function () {
        TestConfig::set('cms.settings.aSocialAuth.context', 'web');

        expect(Config::resolveContext('https://example.test/manager/'))->toBe('web');
    });
});
