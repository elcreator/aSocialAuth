<?php

use Elcreator\aSocialAuth\Enums\SocialProvider;
use Elcreator\aSocialAuth\Support\Config;
use Elcreator\aSocialAuth\Support\HybridAuthManager;
use Elcreator\aSocialAuth\Support\UserResolver;

/**
 * The different ways a provider can answer, and the things that quietly block
 * them.
 *
 * HybridAuth's providers are not one protocol. OAuth 2 redirects back with a
 * query string; OpenID Connect may answer by form POST; OpenID 2.0 has no app
 * registration at all and can reply either way. A package that only ever handles
 * "GET with ?code=" works for Google and fails for Apple — and fails at the
 * point where a site has already registered the app and told its users.
 */

describe('protocol families', function () {
    it('drives every family through the same adapter factory', function () {
        // Nothing branches on protocol: HybridAuth's adapter knows how to answer
        // its own provider, and the package only supplies config.
        $factory = file_get_contents(__DIR__ . '/../../src/Support/HybridAuthManager.php');

        foreach (['oauth', 'openid', 'oidc', 'saml'] as $protocol) {
            expect(strtolower($factory))->not->toContain("'{$protocol}'");
        }
    });

    it('builds an adapter for an OAuth 2 provider', function () {
        TestConfig::set('cms.settings.aSocialAuth.providers.google.enabled', true);
        TestConfig::set('cms.settings.aSocialAuth.providers.google.keys', ['id' => 'a', 'secret' => 'b']);

        $config = HybridAuthManager::adapterConfig(insertProvider('google'));

        expect($config['callback'])->toBe('https://example.test/asocialauth/google/callback');
        expect($config['keys'])->toBe(['id' => 'a', 'secret' => 'b']);
    });

    it('builds an adapter for an OpenID provider that has no credentials', function () {
        // Steam and the OpenID family have no app registration; demanding keys
        // would keep a perfectly usable provider permanently hidden.
        TestConfig::set('cms.settings.aSocialAuth.custom_providers', [
            'steam' => ['enabled' => true],
        ]);

        $config = HybridAuthManager::adapterConfig(insertProvider('steam'));

        expect($config['callback'])->toBe('https://example.test/asocialauth/steam/callback');
        expect($config['keys'])->toBe([]);
        expect(\Elcreator\aSocialAuth\Support\ProviderRegistry::hasRequiredKeys('steam'))->toBeTrue();
    });

    it('passes provider-specific adapter options straight through', function () {
        // An in-house Keycloak realm needs a `url`; Apple needs its key ids.
        // These are adapter configuration, not behaviour the package interprets.
        TestConfig::set('cms.settings.aSocialAuth.custom_providers', [
            'acme' => [
                'enabled' => true,
                'adapter' => 'Keycloak',
                'keys'    => ['id' => 'a', 'secret' => 'b'],
                'extra'   => ['url' => 'https://sso.acme.test/', 'realm' => 'staff'],
            ],
        ]);

        $config = HybridAuthManager::adapterConfig(insertProvider('acme', ['adapter' => 'Keycloak']));

        expect($config['url'])->toBe('https://sso.acme.test/');
        expect($config['realm'])->toBe('staff');
    });

    it('lets a site override a scope the catalogue guessed wrong', function () {
        TestConfig::set('cms.settings.aSocialAuth.providers.x.enabled', true);
        TestConfig::set('cms.settings.aSocialAuth.providers.x.keys', ['id' => 'a', 'secret' => 'b']);

        expect(HybridAuthManager::adapterConfig(insertProvider('x'))['scope'])
            ->toBe(SocialProvider::X->defaultScope());

        TestConfig::set('cms.settings.aSocialAuth.providers.x.scope', 'users.read');
        \Elcreator\aSocialAuth\Support\ProviderRegistry::flush();

        expect(HybridAuthManager::adapterConfig(insertProvider('x2', ['adapter' => 'X']))['scope'] ?? null)
            ->toBeNull();
    });
});

describe('the callback route', function () {
    $routes = fn () => file_get_contents(__DIR__ . '/../../src/Http/routes.php');

    it('accepts a form POST as well as a redirect GET', function () use ($routes) {
        // Apple sets response_mode=form_post, and OpenID 2.0 permits a POST
        // indirect response. A GET-only callback 404s both.
        expect($routes())->toContain("Route::match(['get', 'post'], Config::routePattern('callback')");
    });

    it('confirms Apple really does answer by form POST', function () {
        // Not an assumption: the adapter sets it, so the route has to cope.
        $apple = file_get_contents(__DIR__ . '/../../vendor/hybridauth/hybridauth/src/Provider/Apple.php');

        expect($apple)->toContain("'response_mode'");
        expect($apple)->toContain('form_post');
    });

    it('confirms the OpenID adapter reads either verb', function () {
        $openid = file_get_contents(__DIR__ . '/../../vendor/hybridauth/hybridauth/src/Adapter/OpenID.php');

        // $_REQUEST, not $_GET — the adapter itself is verb-agnostic.
        expect($openid)->toContain('$_REQUEST[\'openid_mode\']');
    });

    it('is not CSRF-checked, in either verb', function () use ($routes) {
        $callback = strstr($routes(), "Config::routePattern('callback')");
        $callback = substr($callback, 0, strpos($callback, ';'));

        // A cross-site POST from Apple cannot carry our token. HybridAuth's
        // `state`, checked against the session, is what guards this leg.
        expect($callback)->not->toContain('csrf');
    });
});

describe('what the plugin itself could block', function () {
    it('does not put the routes behind manager authentication', function () {
        // CheckManagerAuth or the Manager middleware on these would make every
        // callback bounce to the login page — the visitor is not signed in yet,
        // that is the entire point.
        $routes = file_get_contents(__DIR__ . '/../../src/Http/routes.php');

        expect($routes)->not->toContain('managerauth');
        expect($routes)->not->toContain('authtoken');
    });

    it('uses the application global middleware, which carries no CSRF', function () {
        // The global stack is StartSession, SessionProxy, SubstituteBindings and
        // ShareErrorsFromSession. VerifyCsrfToken belongs to the 'mgr' stack, so
        // a front-end callback is not subject to it.
        expect(Config::routeMiddleware())->toBe(['GlobalMiddleware']);
    });

    it('constrains the provider segment without excluding any catalogue slug', function () {
        // A tighter pattern than the slugs it must match would 404 real
        // providers — linkedinopenid and stackexchangeopenid are the long ones.
        $pattern = '#^' . Config::providerPattern() . '$#';

        foreach (SocialProvider::cases() as $case) {
            expect((bool) preg_match($pattern, $case->value))
                ->toBeTrue("route pattern excludes {$case->value}");
        }
    });

    it('does not render the widget inside a frame', function () {
        // X-Frame-Options: SAMEORIGIN on a host site would break an iframed
        // login. The modal is a same-document <dialog>, so there is no frame to
        // block and no third-party script to be blocked.
        foreach (['login', 'identities'] as $surface) {
            $view = file_get_contents(
                __DIR__ . '/../../views/widget/' . ($surface === 'login' ? 'login' : 'identities') . '.blade.php'
            );

            expect($view)->not->toContain('<iframe');
        }
    });

    it('loads no third-party assets that a CSP would reject', function () {
        // Icons are inline SVG and styles are inline, so a strict
        // default-src 'self' does not silently blank the buttons.
        foreach (glob(__DIR__ . '/../../views/**/*.blade.php') as $view) {
            $source = file_get_contents($view);

            expect($source)->not->toMatch('#(src|href)\s*=\s*"https?://#');
        }
    });
});

describe('cross-site session survival', function () {
    it('names SameSite as the cause when a POST callback arrives sessionless', function () {
        // With SameSite=Lax — the Evolution CMS default and the browser default —
        // the cookie is withheld on a cross-site POST, so Apple's callback finds
        // an empty session and looks like an abandoned flow. The visitor sees a
        // generic message; the log has to say what actually happened.
        $controller = file_get_contents(
            __DIR__ . '/../../src/Http/Controllers/SocialCallbackController.php'
        );

        expect($controller)->toContain('diagnoseMissingFlow');
        expect($controller)->toContain('SESSION_SAME_SITE=none');
        expect($controller)->toContain("config('session.same_site'");
    });

    it('does not misdiagnose a plain GET callback', function () {
        $controller = file_get_contents(
            __DIR__ . '/../../src/Http/Controllers/SocialCallbackController.php'
        );

        // A top-level GET redirect carries cookies under Lax, so an expired flow
        // there is just an expired flow.
        expect($controller)->toContain("isMethod('POST')");
    });
});

describe('identity handling is provider-agnostic', function () {
    it('names no provider in any conditional', function () {
        // The invariant behind "the absence of an e-mail in the response is the
        // basis": nothing branches on which network the profile came from.
        $offenders = [];

        foreach (packageSources() as $path => $source) {
            if (str_contains($path, 'Enums')) {
                // The catalogue is a lookup table by definition.
                continue;
            }

            foreach (['telegram', 'steam', 'google', 'facebook', 'apple', 'twitter'] as $slug) {
                if (preg_match('/(===|==|!==|!=|in_array).{0,40}[\'"]' . $slug . '[\'"]/i', $source)) {
                    $offenders[] = basename($path) . ' branches on ' . $slug;
                }
            }
        }

        expect($offenders)->toBe([]);
    });

    it('decides the placeholder from the profile alone', function () {
        // UserResolver::createUser reaches for a placeholder when email() is
        // null — never when the provider is one of a known list.
        $resolver = file_get_contents(__DIR__ . '/../../src/Support/UserResolver.php');

        expect($resolver)->toContain('if ($email === null) {');
        expect($resolver)->toContain('static::placeholderEmail(');
    });

    it('gives the same treatment to a nameless profile from any provider', function () {
        foreach (['google', 'telegram', 'steam', 'acme'] as $slug) {
            $profile = makeProfile(['identifier' => $slug . '-1']);

            expect(UserResolver::email($profile))->toBeNull();
            expect(UserResolver::emailIsVerified($profile))->toBeFalse();
            expect(UserResolver::displayName($profile))->toBeNull();
        }
    });
});
