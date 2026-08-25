<?php

use Elcreator\aSocialAuth\Exceptions\SocialAuthException;
use Elcreator\aSocialAuth\Support\UserResolver;

/**
 * Reading a provider profile, and the placeholder-address scheme that lets
 * someone register with nothing but a Telegram account.
 */

describe('identifier', function () {
    it('reads the provider identifier', function () {
        expect(UserResolver::providerUserId(makeProfile(['identifier' => '12345'])))->toBe('12345');
    });

    it('refuses a profile that identifies nobody', function () {
        // Accepting an empty identifier would let every unidentified visitor
        // collapse onto one shared account.
        expect(fn () => UserResolver::providerUserId(makeProfile(['identifier' => ''])))
            ->toThrow(SocialAuthException::class);

        expect(fn () => UserResolver::providerUserId(makeProfile()))
            ->toThrow(SocialAuthException::class);

        expect(fn () => UserResolver::providerUserId(makeProfile(['identifier' => '   '])))
            ->toThrow(SocialAuthException::class);
    });

    it('keeps a numeric-looking identifier as a string', function () {
        // Provider ids exceed PHP's integer range (Facebook's already do), so
        // they are compared and stored as strings throughout.
        expect(UserResolver::providerUserId(makeProfile(['identifier' => 10215678901234567])))
            ->toBeString();
    });
});

describe('email', function () {
    it('reads a valid address', function () {
        expect(UserResolver::email(makeProfile(['email' => 'a@b.test'])))->toBe('a@b.test');
    });

    it('treats a missing or malformed address as none', function () {
        expect(UserResolver::email(makeProfile()))->toBeNull();
        expect(UserResolver::email(makeProfile(['email' => ''])))->toBeNull();
        expect(UserResolver::email(makeProfile(['email' => 'not-an-address'])))->toBeNull();
    });

    it('only calls an address verified when the provider echoes it back', function () {
        // HybridAuth asserts verification by returning the same address in
        // emailVerified. Anything else is the provider staying silent, which is
        // not the same as vouching for it.
        expect(UserResolver::emailIsVerified(makeProfile([
            'email'         => 'a@b.test',
            'emailVerified' => 'a@b.test',
        ])))->toBeTrue();

        expect(UserResolver::emailIsVerified(makeProfile([
            'email'         => 'a@b.test',
            'emailVerified' => '',
        ])))->toBeFalse();

        expect(UserResolver::emailIsVerified(makeProfile([
            'email'         => 'a@b.test',
            'emailVerified' => 'someone-else@b.test',
        ])))->toBeFalse();

        expect(UserResolver::emailIsVerified(makeProfile(['emailVerified' => 'a@b.test'])))->toBeFalse();
    });

    it('ignores case when comparing the verified address', function () {
        expect(UserResolver::emailIsVerified(makeProfile([
            'email'         => 'A@B.test',
            'emailVerified' => 'a@b.TEST',
        ])))->toBeTrue();
    });
});

describe('display name', function () {
    it('prefers the display name', function () {
        expect(UserResolver::displayName(makeProfile(['displayName' => 'Ada L'])))->toBe('Ada L');
    });

    it('falls back to the name parts', function () {
        expect(UserResolver::displayName(makeProfile([
            'firstName' => 'Ada',
            'lastName'  => 'Lovelace',
        ])))->toBe('Ada Lovelace');
    });

    it('returns null when the provider gave no name at all', function () {
        expect(UserResolver::displayName(makeProfile()))->toBeNull();
    });
});

describe('placeholder addresses', function () {
    it('mints a non-deliverable address for a provider that returns none', function () {
        // Telegram never returns an e-mail, but Evolution CMS requires a unique
        // non-empty one on every user — so registering with nothing but a
        // Telegram account has to produce something storable.
        $email = UserResolver::placeholderEmail(makeProvider('telegram'), '987654321');

        expect($email)->toStartWith('telegram-');
        expect($email)->toEndWith('@social.invalid');
        expect(filter_var($email, FILTER_VALIDATE_EMAIL))->not->toBeFalse();
    });

    it('is stable for the same identity and distinct across identities', function () {
        $provider = makeProvider('telegram');

        expect(UserResolver::placeholderEmail($provider, '1'))
            ->toBe(UserResolver::placeholderEmail($provider, '1'));

        expect(UserResolver::placeholderEmail($provider, '1'))
            ->not->toBe(UserResolver::placeholderEmail($provider, '2'));
    });

    it('does not collide across providers for the same identifier', function () {
        expect(UserResolver::placeholderEmail(makeProvider('telegram'), '1'))
            ->not->toBe(UserResolver::placeholderEmail(makeProvider('steam'), '1'));
    });

    it('uses the configured domain', function () {
        TestConfig::set('cms.settings.aSocialAuth.registration.placeholder_email_domain', 'noreply.example');

        expect(UserResolver::placeholderEmail(makeProvider('telegram'), '1'))
            ->toEndWith('@noreply.example');
    });

    it('recognises its own placeholders and leaves real addresses alone', function () {
        $placeholder = UserResolver::placeholderEmail(makeProvider('telegram'), '1');

        expect(UserResolver::isPlaceholderEmail($placeholder))->toBeTrue();
        expect(UserResolver::isPlaceholderEmail('ada@example.test'))->toBeFalse();
        expect(UserResolver::isPlaceholderEmail(null))->toBeFalse();
        expect(UserResolver::isPlaceholderEmail(''))->toBeFalse();
    });

    it('recognises a placeholder whatever its case', function () {
        expect(UserResolver::isPlaceholderEmail('TELEGRAM-ABC@SOCIAL.INVALID'))->toBeTrue();
    });
});
