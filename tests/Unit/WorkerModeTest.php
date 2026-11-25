<?php

use Elcreator\aSocialAuth\Support\HybridAuthManager;
use Elcreator\aSocialAuth\Support\OneTap;
use Elcreator\aSocialAuth\Support\ProviderRegistry;
use Elcreator\aSocialAuth\Support\Renderer;
use Elcreator\aSocialAuth\Support\RequestState;
use Elcreator\aSocialAuth\Support\SessionCookie;
use Hybridauth\HttpClient\Util;

/**
 * In a worker one process serves many visitors, so whatever lasts "one request"
 * has to end with it.
 */

/** What a worker does between two requests. */
function nextRequest(): void
{
    $_SERVER['REQUEST_TIME_FLOAT'] = (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? 0) + 1;
    $_SESSION = ['_token' => 'csrf'];
}

beforeEach(function () {
    TestConfig::set('cms.settings.aSocialAuth.enable', true);
    TestConfig::set('cms.settings.aSocialAuth.providers.google.enabled', true);
    TestConfig::set('cms.settings.aSocialAuth.providers.google.keys', ['id' => 'id', 'secret' => 's']);
    TestConfig::set('cms.settings.aSocialAuth.providers.google.one_tap', ['enabled' => true]);
    nextRequest();
});

it('keeps a value for the request that set it only', function () {
    RequestState::set('x', 1);
    expect(RequestState::get('x'))->toBe(1);

    nextRequest();
    expect(RequestState::get('x'))->toBeNull();
});

it('prompts and styles the page of the next visitor too', function () {
    $first = OneTap::viewData();
    expect($first)->not->toBeNull()
        ->and(OneTap::viewData())->toBeNull()
        ->and(Renderer::shouldEmitStyles())->toBeTrue();

    nextRequest();

    $second = OneTap::viewData();
    expect($second)->not->toBeNull()
        ->and($second['nonce'])->not->toBe($first['nonce'])
        ->and(Renderer::shouldEmitStyles())->toBeTrue();
});

it('reads the providers again for the next request', function () {
    expect(ProviderRegistry::resolve('google'))->not->toBeNull();

    TestConfig::set('cms.settings.aSocialAuth.providers.google.enabled', false);
    nextRequest();

    expect(ProviderRegistry::resolve('google'))->toBeNull();
});

it('does not carry a wanted session cookie over to the next visitor', function () {
    SessionCookie::want();
    expect(SessionCookie::wanted())->toBeTrue();

    nextRequest();
    expect(SessionCookie::wanted())->toBeFalse();
});

it('gets the provider URL from HybridAuth instead of letting it exit', function () {
    $adapter = new class ([
        'callback' => 'https://example.test/cb',
        'keys'     => ['id' => 'id', 'secret' => 's'],
    ]) extends \Hybridauth\Provider\Google {
    };

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $url = HybridAuthManager::begin($adapter);

    expect($url)->toStartWith('https://accounts.google.com/')
        ->and($url)->toContain('state=');

    // Not left behind for whoever else uses HybridAuth in this process.
    $handler = new ReflectionProperty(Util::class, 'redirectHandler');
    expect($handler->getValue())->toBeNull();
});
