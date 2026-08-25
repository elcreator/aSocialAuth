<?php

use Elcreator\aSocialAuth\Enums\SocialProvider;

/**
 * The catalogue must stay in step with the HybridAuth version installed —
 * that is the whole promise of "everything HybridAuth provides".
 */

it('has a case for every HybridAuth provider that ships with the library', function () {
    $dir = __DIR__ . '/../../vendor/hybridauth/hybridauth/src/Provider';

    expect(is_dir($dir))->toBeTrue('HybridAuth provider directory is missing');

    $shipped = collect(glob($dir . '/*.php'))
        ->map(fn (string $file) => basename($file, '.php'))
        ->sort()
        ->values();

    $catalogued = collect(SocialProvider::cases())
        ->map(fn (SocialProvider $case) => $case->hybridauthProvider())
        ->sort()
        ->values();

    expect($catalogued->all())->toEqual($shipped->all());
});

it('derives each slug as the lowercased adapter class name', function () {
    foreach (SocialProvider::cases() as $case) {
        expect($case->value)->toBe(strtolower($case->hybridauthProvider()));
    }
});

it('builds a resolvable adapter class for every case', function () {
    foreach (SocialProvider::cases() as $case) {
        expect($case->adapterClass())->toBe('Hybridauth\\Provider\\' . $case->hybridauthProvider());
        expect(class_exists($case->adapterClass()))
            ->toBeTrue("adapter missing for {$case->value}");
    }
});

it('gives every case a non-empty label', function () {
    foreach (SocialProvider::cases() as $case) {
        expect(trim($case->label()))->not->toBe('');
    }
});

it('resolves slugs case-insensitively and ignores surrounding whitespace', function () {
    expect(SocialProvider::fromSlug('GOOGLE'))->toBe(SocialProvider::GOOGLE);
    expect(SocialProvider::fromSlug('  telegram '))->toBe(SocialProvider::TELEGRAM);
    expect(SocialProvider::fromSlug('nope'))->toBeNull();
    expect(SocialProvider::fromSlug(null))->toBeNull();
});

it('holds no opinion about which providers return an e-mail', function () {
    // Deliberately absent. Whether an address arrives is a property of the
    // *response*, not of the provider, and a hardcoded list could only rot: X
    // gained an e-mail scope, LinkedIn moved to OpenID, and any provider can
    // return nothing for a particular user who withheld consent. The placeholder
    // address is chosen by UserResolver when the profile carries no usable
    // address, whichever provider it came from.
    expect(method_exists(SocialProvider::class, 'providesEmail'))->toBeFalse();
    expect(method_exists(SocialProvider::class, 'emaillessProviders'))->toBeFalse();
});

it('does not demand credentials from the OpenID-based providers', function () {
    // They have no app registration to get credentials from, so requiring them
    // would keep a perfectly usable provider permanently hidden.
    expect(SocialProvider::OPENID->requiredKeys())->toBe([]);
    expect(SocialProvider::STEAM->requiredKeys())->toBe([]);
    expect(SocialProvider::AOLOPENID->requiredKeys())->toBe([]);

    expect(SocialProvider::GOOGLE->requiredKeys())->toBe(['id', 'secret']);
});

it('exposes the catalogue as a slug => label map', function () {
    $catalogue = SocialProvider::catalogue();

    expect($catalogue)->toHaveCount(count(SocialProvider::cases()));
    expect($catalogue['google'])->toBe('Google');
    expect($catalogue)->toHaveKey('telegram');
});
