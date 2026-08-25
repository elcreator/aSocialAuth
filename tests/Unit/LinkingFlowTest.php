<?php

use Elcreator\aSocialAuth\Exceptions\SocialAuthException;
use Elcreator\aSocialAuth\Models\SocialAccount;
use Elcreator\aSocialAuth\Support\Identity;
use Elcreator\aSocialAuth\Support\UserResolver;

/**
 * The merge flow, against a real database.
 *
 * One person, several providers, one account: sign in with Google today, add
 * Telegram tomorrow, and either one opens the same account from then on.
 */

it('links a new identity and signs the existing user in on the next visit', function () {
    $userId   = makeUser(['email' => 'ada@example.test']);
    $provider = insertProvider('google');
    $profile  = makeProfile([
        'identifier'    => 'g-1',
        'email'         => 'ada@example.test',
        'emailVerified' => 'ada@example.test',
        'displayName'   => 'Ada',
    ]);

    $first = UserResolver::forLogin($provider, $profile);

    expect($first['user_id'])->toBe($userId);
    expect($first['created'])->toBeFalse();
    expect(SocialAccount::countForUser($userId))->toBe(1);

    // Second visit resolves through the stored identity, not the e-mail.
    $second = UserResolver::forLogin($provider, $profile);

    expect($second['user_id'])->toBe($userId);
    expect(SocialAccount::countForUser($userId))->toBe(1);
});

it('adds a second provider to the same account', function () {
    $userId = makeUser(['email' => 'ada@example.test']);
    $google = insertProvider('google');
    $tg     = insertProvider('telegram');

    UserResolver::forLogin($google, makeProfile([
        'identifier'    => 'g-1',
        'email'         => 'ada@example.test',
        'emailVerified' => 'ada@example.test',
    ]));

    // The widget's "connect" path: the user is signed in and adds Telegram,
    // which returns no e-mail at all.
    UserResolver::forLink($tg, makeProfile(['identifier' => 'tg-9']), $userId);

    expect(SocialAccount::countForUser($userId))->toBe(2);
    expect(Identity::linkedSlugs($userId))->toEqualCanonicalizing(['google', 'telegram']);

    // And now Telegram alone signs that same user in.
    $viaTelegram = UserResolver::forLogin($tg, makeProfile(['identifier' => 'tg-9']));

    expect($viaTelegram['user_id'])->toBe($userId);
    expect($viaTelegram['created'])->toBeFalse();
});

it('refuses to move an identity that belongs to somebody else', function () {
    $ada  = makeUser(['email' => 'ada@example.test']);
    $bob  = makeUser(['email' => 'bob@example.test']);
    $tg   = insertProvider('telegram');

    UserResolver::forLink($tg, makeProfile(['identifier' => 'tg-9']), $ada);

    // Silently reassigning would let anyone who can authenticate with a provider
    // detach it from its owner.
    expect(fn () => UserResolver::forLink($tg, makeProfile(['identifier' => 'tg-9']), $bob))
        ->toThrow(SocialAuthException::class);

    expect(SocialAccount::countForUser($ada))->toBe(1);
    expect(SocialAccount::countForUser($bob))->toBe(0);
});

it('is idempotent when a user re-links a provider they already have', function () {
    $userId = makeUser();
    $google = insertProvider('google');

    UserResolver::forLink($google, makeProfile(['identifier' => 'g-1', 'displayName' => 'Ada']), $userId);
    UserResolver::forLink($google, makeProfile(['identifier' => 'g-1', 'displayName' => 'Ada Lovelace']), $userId);

    expect(SocialAccount::countForUser($userId))->toBe(1);
    // The cached profile is refreshed rather than duplicated.
    expect(SocialAccount::forUser($userId)->first()->name)->toBe('Ada Lovelace');
});

it('keeps one identity per provider per person at the database level', function () {
    $ada    = makeUser(['email' => 'ada@example.test']);
    $google = insertProvider('google');

    UserResolver::forLink($google, makeProfile(['identifier' => 'g-1']), $ada);

    // The unique index is the backstop behind the application checks.
    expect(fn () => SocialAccount::query()->create([
        'provider_id'      => $google->getKey(),
        'provider_user_id' => 'g-1',
        'user_id'          => 999,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

describe('matching by e-mail', function () {
    it('attaches to an account that has verified the same address', function () {
        $userId   = makeUser(['email' => 'ada@example.test', 'verified' => 1]);
        $provider = insertProvider('google');

        $result = UserResolver::forLogin($provider, makeProfile([
            'identifier'    => 'g-1',
            'email'         => 'ada@example.test',
            'emailVerified' => 'ada@example.test',
        ]));

        expect($result['user_id'])->toBe($userId);
    });

    it('refuses when the provider did not verify the address', function () {
        makeUser(['email' => 'ada@example.test', 'verified' => 1]);
        $provider = insertProvider('google');

        // Without provider verification the address is just a string the account
        // holder typed, and matching on it would be a takeover route.
        expect(fn () => UserResolver::forLogin($provider, makeProfile([
            'identifier' => 'g-1',
            'email'      => 'ada@example.test',
        ])))->toThrow(SocialAuthException::class);
    });

    it('refuses when the local account never proved its own address', function () {
        // Registering with someone else's address and waiting for them to sign
        // in with Google would otherwise collect their identity.
        makeUser(['email' => 'ada@example.test', 'verified' => 0]);
        $provider = insertProvider('google');

        expect(fn () => UserResolver::forLogin($provider, makeProfile([
            'identifier'    => 'g-1',
            'email'         => 'ada@example.test',
            'emailVerified' => 'ada@example.test',
        ])))->toThrow(SocialAuthException::class);
    });

    it('can be relaxed by a site that accepts the trade', function () {
        $userId = makeUser(['email' => 'ada@example.test', 'verified' => 0]);
        TestConfig::set('cms.settings.aSocialAuth.linking.require_local_verified_email', false);
        $provider = insertProvider('google');

        $result = UserResolver::forLogin($provider, makeProfile([
            'identifier'    => 'g-1',
            'email'         => 'ada@example.test',
            'emailVerified' => 'ada@example.test',
        ]));

        expect($result['user_id'])->toBe($userId);
    });

    it('never matches on a placeholder address', function () {
        // Two Telegram sign-ups must not collapse into one account just because
        // neither has a real e-mail.
        $tg      = insertProvider('telegram');
        $userId  = makeUser(['email' => UserResolver::placeholderEmail($tg, 'tg-1'), 'verified' => 0]);

        expect(fn () => UserResolver::forLogin($tg, makeProfile(['identifier' => 'tg-2'])))
            ->toThrow(SocialAuthException::class);

        expect(SocialAccount::countForUser($userId))->toBe(0);
    });
});

describe('when nothing matches', function () {
    it('refuses the sign-in while registration is closed', function () {
        $provider = insertProvider('google');

        expect(fn () => UserResolver::forLogin($provider, makeProfile([
            'identifier'    => 'g-1',
            'email'         => 'nobody@example.test',
            'emailVerified' => 'nobody@example.test',
        ])))->toThrow(SocialAuthException::class, __('aSocialAuth::login.error_no_user'));
    });
});

describe('unlinking', function () {
    it('knows when an identity is the last one', function () {
        $userId = makeUser();
        $google = insertProvider('google');
        $tg     = insertProvider('telegram');

        UserResolver::forLink($google, makeProfile(['identifier' => 'g-1']), $userId);
        expect(Identity::isLastIdentity($userId))->toBeTrue();

        UserResolver::forLink($tg, makeProfile(['identifier' => 'tg-1']), $userId);
        expect(Identity::isLastIdentity($userId))->toBeFalse();
    });

    it('lists only the providers a user has not linked yet', function () {
        $userId = makeUser();

        TestConfig::set('cms.settings.aSocialAuth.providers.google.enabled', true);
        TestConfig::set('cms.settings.aSocialAuth.providers.google.keys', ['id' => 'a', 'secret' => 'b']);
        TestConfig::set('cms.settings.aSocialAuth.providers.telegram.enabled', true);
        TestConfig::set('cms.settings.aSocialAuth.providers.telegram.keys', ['id' => 'a', 'secret' => 'b']);

        expect(\Elcreator\aSocialAuth\Support\ProviderRegistry::enabled())->toHaveCount(2);

        $google = \Elcreator\aSocialAuth\Support\ProviderRegistry::find('google');
        UserResolver::forLink($google, makeProfile(['identifier' => 'g-1']), $userId);

        expect(Identity::connectableProviders($userId)->pluck('slug')->all())->toBe(['telegram']);
    });
});
