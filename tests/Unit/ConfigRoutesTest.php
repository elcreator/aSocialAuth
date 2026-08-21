<?php

use EvolutionCMS\aSocialAuth\Support\Config;

/**
 * The routes have to be genuinely configurable: changing the prefix must move
 * every URL the package produces, including the callback URLs registered with
 * the providers, because a prefix that only moved half of them would silently
 * break the OAuth round trip.
 */

it('builds every URL from the default prefix', function () {
    expect(Config::getRoutePrefix())->toBe('asocialauth');

    expect(Config::buildLoginUrl('google'))->toBe('https://example.test/asocialauth/google');
    expect(Config::buildCallbackUrl('google'))->toBe('https://example.test/asocialauth/google/callback');
    expect(Config::buildLinkUrl('google'))->toBe('https://example.test/asocialauth/google/link');
    expect(Config::buildUnlinkUrl('google'))->toBe('https://example.test/asocialauth/google/unlink');
});

it('moves every URL when the prefix changes', function () {
    TestConfig::set('cms.settings.aSocialAuth.routes.prefix', 'auth');

    expect(Config::buildLoginUrl('telegram'))->toBe('https://example.test/auth/telegram');
    expect(Config::buildCallbackUrl('telegram'))->toBe('https://example.test/auth/telegram/callback');
    expect(Config::buildLinkUrl('telegram'))->toBe('https://example.test/auth/telegram/link');
    expect(Config::buildUnlinkUrl('telegram'))->toBe('https://example.test/auth/telegram/unlink');
});

it('honours fully rewritten route patterns', function () {
    TestConfig::set('cms.settings.aSocialAuth.routes.prefix', 'вхід');
    TestConfig::set('cms.settings.aSocialAuth.routes.patterns', [
        'login'    => 'in/{provider}',
        'callback' => 'in/{provider}/back',
        'link'     => 'add/{provider}',
        'unlink'   => 'drop/{provider}',
    ]);

    expect(Config::buildLoginUrl('google'))->toBe('https://example.test/вхід/in/google');
    expect(Config::buildCallbackUrl('google'))->toBe('https://example.test/вхід/in/google/back');
    expect(Config::buildLinkUrl('google'))->toBe('https://example.test/вхід/add/google');
    expect(Config::buildUnlinkUrl('google'))->toBe('https://example.test/вхід/drop/google');
});

it('tolerates a prefix written with stray slashes', function () {
    TestConfig::set('cms.settings.aSocialAuth.routes.prefix', '/social/');

    expect(Config::getRoutePrefix())->toBe('social');
    expect(Config::buildCallbackUrl('x'))->toBe('https://example.test/social/x/callback');
});

it('supports an empty prefix so routes can sit at the site root', function () {
    TestConfig::set('cms.settings.aSocialAuth.routes.prefix', '');
    // The legacy top-level key must not resurrect the old prefix here.
    TestConfig::set('cms.settings.aSocialAuth.route_prefix', '');

    expect(Config::buildLoginUrl('google'))->toBe('https://example.test/google');
});

it('still honours the pre-routes-block route_prefix key', function () {
    // An install that published the old config shape must not lose its URLs to
    // an upgrade — the provider consoles are registered against them.
    TestConfig::set('cms.settings.aSocialAuth.routes.prefix', null);
    TestConfig::set('cms.settings.aSocialAuth.route_prefix', 'legacy-prefix');

    expect(Config::getRoutePrefix())->toBe('legacy-prefix');
    expect(Config::buildCallbackUrl('google'))->toBe('https://example.test/legacy-prefix/google/callback');
});

it('falls back to a sane pattern when one is configured empty', function () {
    TestConfig::set('cms.settings.aSocialAuth.routes.patterns.callback', '');

    expect(Config::routePattern('callback'))->toBe('{provider}/callback');
});

it('uses the application global middleware unless told otherwise', function () {
    expect(Config::routeMiddleware())->toBe(['GlobalMiddleware']);

    TestConfig::set('cms.settings.aSocialAuth.routes.middleware', ['Custom']);
    expect(Config::routeMiddleware())->toBe(['Custom']);
});

it('names routes with the configured prefix', function () {
    expect(Config::routeName('callback'))->toBe('asocialauth.callback');

    TestConfig::set('cms.settings.aSocialAuth.routes.name_prefix', 'social.');
    expect(Config::routeName('callback'))->toBe('social.callback');
});

it('url-encodes the provider segment', function () {
    // The segment is constrained by the router too, but a URL built for a link
    // in a view does not go through the router first.
    expect(Config::buildLoginUrl('a b'))->toBe('https://example.test/asocialauth/a%20b');
});
