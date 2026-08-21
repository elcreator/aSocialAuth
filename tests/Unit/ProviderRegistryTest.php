<?php

use EvolutionCMS\aSocialAuth\Support\ProviderRegistry;

/**
 * Config normalisation and the credential gate. These run without a database:
 * everything asserted here is decided before the registry table is touched.
 */

it('normalises the configured providers into one slug-keyed map', function () {
    $configured = ProviderRegistry::configured();

    expect($configured)->toHaveKeys(['google', 'facebook', 'linkedin', 'x', 'telegram']);
});

it('merges custom providers over scaffolded ones', function () {
    TestConfig::set('cms.settings.aSocialAuth.custom_providers', [
        'google' => ['label' => 'Corporate Google', 'enabled' => true],
    ]);

    $entry = ProviderRegistry::configFor('google');

    expect($entry['label'])->toBe('Corporate Google');
    // The merge must not drop what the base entry carried.
    expect($entry)->toHaveKey('keys');
});

it('accepts a custom provider the catalogue has never heard of', function () {
    TestConfig::set('cms.settings.aSocialAuth.custom_providers', [
        'acme' => [
            'enabled' => true,
            'label'   => 'Acme SSO',
            'adapter' => 'Keycloak',
            'keys'    => ['id' => 'a', 'secret' => 'b'],
        ],
    ]);

    expect(ProviderRegistry::configured())->toHaveKey('acme');
    expect(ProviderRegistry::configFor('acme')['adapter'])->toBe('Keycloak');
});

it('ignores slugs that could not be a URL segment', function () {
    // The router constrains {provider}, but a bad slug in config would otherwise
    // produce a registry row and a button that leads to a 404.
    TestConfig::set('cms.settings.aSocialAuth.custom_providers', [
        'bad slug'   => ['enabled' => true, 'adapter' => 'X'],
        'UPPER'      => ['enabled' => true, 'adapter' => 'X'],
        'with-dash'  => ['enabled' => true, 'adapter' => 'X'],
        ''           => ['enabled' => true, 'adapter' => 'X'],
    ]);

    $configured = ProviderRegistry::configured();

    expect($configured)->not->toHaveKey('bad slug');
    expect($configured)->not->toHaveKey('with-dash');
    expect($configured)->not->toHaveKey('');
    // Case is folded rather than rejected, so 'UPPER' becomes 'upper'.
    expect($configured)->toHaveKey('upper');
});

it('ignores entries that are not arrays', function () {
    TestConfig::set('cms.settings.aSocialAuth.custom_providers', ['junk' => 'nope']);

    expect(ProviderRegistry::configured())->not->toHaveKey('junk');
});

describe('the credential gate', function () {
    it('hides a provider whose credentials are missing', function () {
        // Enabled with empty keys is the half-finished .env case; a button here
        // would only lead to a failure at the provider.
        TestConfig::set('cms.settings.aSocialAuth.providers.google.enabled', true);
        TestConfig::set('cms.settings.aSocialAuth.providers.google.keys', ['id' => '', 'secret' => '']);

        expect(ProviderRegistry::hasRequiredKeys('google'))->toBeFalse();
    });

    it('hides a provider with only half its credentials', function () {
        TestConfig::set('cms.settings.aSocialAuth.providers.google.keys', ['id' => 'abc', 'secret' => '']);

        expect(ProviderRegistry::hasRequiredKeys('google'))->toBeFalse();
    });

    it('treats whitespace-only credentials as missing', function () {
        TestConfig::set('cms.settings.aSocialAuth.providers.google.keys', ['id' => '  ', 'secret' => "\t"]);

        expect(ProviderRegistry::hasRequiredKeys('google'))->toBeFalse();
    });

    it('accepts a provider once both credentials are filled in', function () {
        TestConfig::set('cms.settings.aSocialAuth.providers.google.keys', ['id' => 'abc', 'secret' => 'def']);

        expect(ProviderRegistry::hasRequiredKeys('google'))->toBeTrue();
    });

    it('does not demand credentials from an OpenID provider', function () {
        TestConfig::set('cms.settings.aSocialAuth.custom_providers', [
            'steam' => ['enabled' => true],
        ]);

        expect(ProviderRegistry::hasRequiredKeys('steam'))->toBeTrue();
    });

    it('reads credentials out of config and never out of the registry', function () {
        // Secrets must not reach the table: a database dump is a routine
        // artefact and OAuth secrets have no business travelling with it.
        TestConfig::set('cms.settings.aSocialAuth.providers.google.keys', ['id' => 'x', 'secret' => 'y']);

        expect(ProviderRegistry::credentials('google'))->toBe(['id' => 'x', 'secret' => 'y']);

        $registryColumns = (new \EvolutionCMS\aSocialAuth\Models\RegisteredProvider())->getFillable();

        expect($registryColumns)->not->toContain('keys');
        expect($registryColumns)->not->toContain('secret');
    });
});

describe('syncing config into the table', function () {
    it('registers a configured provider and offers it', function () {
        TestConfig::set('cms.settings.aSocialAuth.providers.google.enabled', true);
        TestConfig::set('cms.settings.aSocialAuth.providers.google.keys', ['id' => 'a', 'secret' => 'b']);

        $enabled = ProviderRegistry::enabled();

        expect($enabled)->toHaveCount(1);
        expect($enabled->first()->slug)->toBe('google');
        expect($enabled->first()->adapter)->toBe('Google');
    });

    it('orders providers by their configured sort', function () {
        foreach (['google' => 30, 'facebook' => 10, 'linkedin' => 20] as $slug => $sort) {
            TestConfig::set("cms.settings.aSocialAuth.providers.{$slug}.enabled", true);
            TestConfig::set("cms.settings.aSocialAuth.providers.{$slug}.keys", ['id' => 'a', 'secret' => 'b']);
            TestConfig::set("cms.settings.aSocialAuth.providers.{$slug}.sort", $sort);
        }

        expect(ProviderRegistry::enabled()->pluck('slug')->all())
            ->toBe(['facebook', 'linkedin', 'google']);
    });

    it('hides a registered provider whose credentials went missing', function () {
        TestConfig::set('cms.settings.aSocialAuth.providers.google.enabled', true);
        TestConfig::set('cms.settings.aSocialAuth.providers.google.keys', ['id' => 'a', 'secret' => 'b']);

        expect(ProviderRegistry::enabled())->toHaveCount(1);

        // The row still says enabled; the button must still disappear, because
        // it would only lead to a failure at the provider.
        ProviderRegistry::flush();
        TestConfig::set('cms.settings.aSocialAuth.providers.google.keys', ['id' => '', 'secret' => '']);

        expect(ProviderRegistry::enabled())->toHaveCount(0);
    });

    it('disables a provider dropped from config instead of deleting it', function () {
        TestConfig::set('cms.settings.aSocialAuth.providers.google.enabled', true);
        TestConfig::set('cms.settings.aSocialAuth.providers.google.keys', ['id' => 'a', 'secret' => 'b']);
        ProviderRegistry::enabled();

        ProviderRegistry::flush();
        TestConfig::set('cms.settings.aSocialAuth.providers', []);
        ProviderRegistry::sync();

        // Deleting the row would orphan every identity linked to it.
        $row = ProviderRegistry::find('google');

        expect($row)->not->toBeNull();
        expect($row->enabled)->toBeFalse();
    });

    it('finds a disabled provider so its identities stay removable', function () {
        insertProvider('google', ['enabled' => false]);

        expect(ProviderRegistry::resolve('google'))->toBeNull();
        expect(ProviderRegistry::find('google'))->not->toBeNull();
    });

    it('updates a label without creating a second row', function () {
        TestConfig::set('cms.settings.aSocialAuth.providers.google.enabled', true);
        TestConfig::set('cms.settings.aSocialAuth.providers.google.keys', ['id' => 'a', 'secret' => 'b']);
        ProviderRegistry::enabled();

        ProviderRegistry::flush();
        TestConfig::set('cms.settings.aSocialAuth.providers.google.label', 'Work Google');
        ProviderRegistry::sync();

        expect(\EvolutionCMS\aSocialAuth\Models\RegisteredProvider::query()->where('slug', 'google')->count())->toBe(1);
        expect(ProviderRegistry::find('google')->label)->toBe('Work Google');
    });
});

it('survives a database it cannot reach', function () {
    // Migrations not run yet: the login page must still render, just without
    // buttons, rather than taking the site down.
    \Illuminate\Database\Capsule\Manager::schema()->drop('social_providers');

    expect(ProviderRegistry::enabled()->all())->toBe([]);
    expect(ProviderRegistry::resolve('google'))->toBeNull();
    expect(ProviderRegistry::find('google'))->toBeNull();
});
