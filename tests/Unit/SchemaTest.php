<?php

use Elcreator\aSocialAuth\Models\RegisteredProvider;
use Elcreator\aSocialAuth\Models\SocialAccount;

/**
 * The data model: a provider is a row, an identity is a row, and neither is a
 * column on the user.
 *
 * The shape this replaces is one nullable id column per network on the user row
 * (`fb_id`, `tw_id`, `gg_id`, `li_id`, `tg_id`, …). That works for four networks
 * and stops working at forty: every addition is a migration, "which provider is
 * this?" becomes a chain of null checks, and a user can hold at most one
 * identity per network because the column is the storage.
 */

it('links an identity to a provider by foreign key, not by a slug string', function () {
    expect((new SocialAccount())->getFillable())->toContain('provider_id');
    expect((new SocialAccount())->getFillable())->not->toContain('provider');
});

it('keeps identities in their own table rather than on the user', function () {
    expect((new SocialAccount())->getTable())->toBe('social_accounts');
    expect((new RegisteredProvider())->getTable())->toBe('social_providers');
});

it('casts the identity columns it compares and stores', function () {
    $casts = (new SocialAccount())->getCasts();

    expect($casts['provider_id'])->toBe('int');
    expect($casts['user_id'])->toBe('int');
    expect($casts['email_verified'])->toBe('bool');
});

it('does not cast the provider-side identifier to a number', function () {
    // Facebook ids already exceed PHP's integer range, so the identifier is
    // compared and stored as a string throughout.
    expect((new SocialAccount())->getCasts())->not->toHaveKey('provider_user_id');
});

it('adds a network without adding a column', function () {
    // Enabling a fortieth provider must touch nothing but configuration.
    $columns = (new SocialAccount())->getFillable();

    sort($columns);

    expect($columns)->toBe([
        'avatar',
        'email',
        'email_verified',
        'last_login_at',
        'name',
        'provider_id',
        'provider_user_id',
        'user_id',
    ]);
});

it('resolves a registry row back to its catalogue entry', function () {
    $provider = makeProvider('google');

    expect($provider->catalogue())->toBe(\Elcreator\aSocialAuth\Enums\SocialProvider::GOOGLE);
    expect($provider->adapterClass())->toBe('Hybridauth\\Provider\\Google');
    
});

it('lets a site point a slug at an adapter of its own', function () {
    $provider = makeProvider('acme', ['adapter' => 'Acme\\Sso\\Adapter']);

    // A fully qualified name is taken as-is; a bare one is read as a HybridAuth
    // provider. That is what makes an in-house SSO a config change.
    expect($provider->adapterClass())->toBe('Acme\\Sso\\Adapter');
});

it('has no catalogue entry for an unknown custom provider', function () {
    // And needs none: nothing about handling a sign-in depends on the catalogue
    // recognising the provider.
    expect(makeProvider('acme', ['adapter' => 'Keycloak'])->catalogue())->toBeNull();
});

it('falls back to the slug for a provider icon', function () {
    expect(makeProvider('google')->iconKey())->toBe('google');
    expect(makeProvider('x', ['icon' => 'twitter'])->iconKey())->toBe('twitter');
});

describe('the migration that normalises existing rows', function () {
    it('preserves identities rather than dropping them', function () {
        $migration = file_get_contents(
            __DIR__ . '/../../database/migrations/2026_03_11_000004_link_social_accounts_to_providers.php'
        );

        // Every slug already stored gets a registry row and the rows are
        // repointed before the old column goes.
        expect($migration)->toContain('backfillProviderIds');
        expect($migration)->toContain('registerSlug');

        // Registered disabled: config decides what is on offer, and a migration
        // must not silently start showing a button.
        expect($migration)->toContain("'enabled'    => false");
    });

    it('disables rather than deletes a provider that is no longer configured', function () {
        // Deleting one would orphan every identity linked to it, and a provider
        // is switched off far more often than it is truly retired.
        $registry = file_get_contents(__DIR__ . '/../../src/Support/ProviderRegistry.php');

        expect($registry)->toContain("'enabled' => false");
        expect($registry)->not->toContain('->delete()');
    });
});
