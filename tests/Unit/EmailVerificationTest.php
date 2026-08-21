<?php

use EvolutionCMS\aSocialAuth\Exceptions\SocialAuthException;
use EvolutionCMS\aSocialAuth\Models\EmailVerification;
use EvolutionCMS\aSocialAuth\Support\Config;
use EvolutionCMS\aSocialAuth\Support\EmailVerifier;
use EvolutionCMS\aSocialAuth\Support\UserResolver;

/** The address currently stored on a user. */
function currentEmail(int $userId): string
{
    return (string) \EvolutionCMS\Models\UserAttribute::query()
        ->where('internalKey', $userId)
        ->value('email');
}

/**
 * Adding and proving an e-mail address after signing up without one.
 *
 * This is what completes a Telegram sign-up: the account starts with a
 * synthesised `@social.invalid` address, the owner claims a real one, and only
 * following the link moves it onto the user.
 *
 * That ordering is the security of the feature, not an implementation detail —
 * see the "claim, then prove" block below.
 */

it('is on by default and gives a claim a day to be proven', function () {
    expect(Config::emailVerificationEnabled())->toBeTrue();
    expect(Config::emailVerificationTtl())->toBe(1440);
});

it('falls back to a day when the configured window is nonsense', function () {
    TestConfig::set('cms.settings.aSocialAuth.credentials.verify_ttl', 0);
    expect(Config::emailVerificationTtl())->toBe(1440);

    TestConfig::set('cms.settings.aSocialAuth.credentials.verify_ttl', -5);
    expect(Config::emailVerificationTtl())->toBe(1440);
});

describe('the confirmation link', function () {
    it('needs no page on the site, unlike the password reset', function () {
        // The route handles the token and redirects, so there is nothing for a
        // template author to place and nothing to configure before it works.
        expect(Config::verifyUrl('abc123'))
            ->toBe('https://example.test/asocialauth/credentials/verify?token=abc123');
    });

    it('follows the configured route prefix', function () {
        TestConfig::set('cms.settings.aSocialAuth.routes.prefix', 'auth');

        expect(Config::verifyUrl('abc123'))
            ->toBe('https://example.test/auth/credentials/verify?token=abc123');
    });

    it('escapes a token that would otherwise break the URL', function () {
        expect(Config::verifyUrl('a b&c=d'))
            ->toBe('https://example.test/asocialauth/credentials/verify?token=a%20b%26c%3Dd');
    });

    it('lands the visitor at the context root unless told otherwise', function () {
        expect(Config::verifyRedirect(Config::CONTEXT_WEB))->toBe('https://example.test/');

        TestConfig::set('cms.settings.aSocialAuth.credentials.verify_redirect', '/members/profile');
        expect(Config::verifyRedirect(Config::CONTEXT_WEB))->toBe('https://example.test/members/profile');
    });
});

describe('claim, then prove', function () {
    it('keeps the claimed address out of the user row', function () {
        // Writing an unproven address onto the user would be enough to collect
        // someone else's provider identity through match_by_email, and enough to
        // stop the rightful owner from ever registering it. So the claim lives
        // in its own table until the link is followed.
        $fillable = (new EmailVerification())->getFillable();

        expect($fillable)->toContain('email');
        expect((new EmailVerification())->getTable())->toBe('social_email_verifications');

        // Nothing in the claim model can touch user_attributes.
        expect($fillable)->not->toContain('verified');
    });

    it('allows one outstanding claim per user', function () {
        // A second claim replaces the first, so an address typed by mistake
        // cannot be confirmed later from an old mail.
        $migration = file_get_contents(
            __DIR__ . '/../../database/migrations/2026_03_11_000005_create_social_email_verifications_table.php'
        );

        expect($migration)->toContain("unique('user_id')");
    });

    it('does not make the claimed address unique across claims', function () {
        // Two people may both be trying to prove the same address; only the
        // first to confirm gets it. Enforcing uniqueness on the claim would let
        // an attacker block a legitimate claim just by starting one.
        $migration = file_get_contents(
            __DIR__ . '/../../database/migrations/2026_03_11_000005_create_social_email_verifications_table.php'
        );

        expect($migration)->not->toContain("unique('email')");
        expect($migration)->toContain("string('token', 64)->unique()");
    });

    it('expires a claim that was never proven', function () {
        $claim = new EmailVerification();
        $claim->forceFill(['expires_at' => date('Y-m-d H:i:s', time() - 60)]);

        expect($claim->hasExpired())->toBeTrue();

        $claim->forceFill(['expires_at' => date('Y-m-d H:i:s', time() + 3600)]);
        expect($claim->hasExpired())->toBeFalse();
    });

    it('treats a claim with no deadline as still open', function () {
        $claim = new EmailVerification();
        $claim->forceFill(['expires_at' => null]);

        expect($claim->hasExpired())->toBeFalse();
    });
});

describe('what may be claimed', function () {
    it('refuses an address in the placeholder domain', function () {
        // The placeholder domain is ours; a claim inside it would make a
        // synthesised address indistinguishable from a proven one.
        $placeholder = UserResolver::placeholderEmail(makeProvider('telegram'), '1');

        expect(UserResolver::isPlaceholderEmail($placeholder))->toBeTrue();
    });

    it('has a distinct message for each way a claim can be rejected', function () {
        $messages = [
            __('aSocialAuth::login.error_email_invalid'),
            __('aSocialAuth::login.error_email_taken'),
            __('aSocialAuth::login.error_email_already_yours'),
            __('aSocialAuth::login.error_email_send'),
            __('aSocialAuth::login.error_verify_token'),
        ];

        expect(array_unique($messages))->toHaveCount(count($messages));
    });
});

describe('matching an identity by e-mail', function () {
    it('requires the local account to have proven its address too', function () {
        // The provider vouching for an address proves the person arriving owns
        // it. It says nothing about the account that already holds it — so
        // without this, registering with someone else's address and waiting
        // would collect their Google identity.
        expect(Config::requireLocalVerifiedEmail())->toBeTrue();
    });

    it('can be relaxed for a site that accepts the trade', function () {
        TestConfig::set('cms.settings.aSocialAuth.linking.require_local_verified_email', false);

        expect(Config::requireLocalVerifiedEmail())->toBeFalse();
    });

    it('still requires the provider to have verified the address', function () {
        expect(Config::requireVerifiedEmail())->toBeTrue();
    });
});

describe('the round trip', function () {
    it('takes a Telegram sign-up from placeholder to a proven address', function () {
        // The case the whole feature exists for.
        $tg     = insertProvider('telegram');
        $userId = makeUser([
            'email'    => UserResolver::placeholderEmail($tg, 'tg-9'),
            'verified' => 0,
        ]);

        expect(EmailVerifier::isVerified($userId))->toBeFalse();

        EmailVerifier::request($userId, 'ada@example.test', Config::CONTEXT_WEB);

        // Claimed, mailed — and deliberately not yet on the account.
        $claim = EmailVerifier::pendingFor($userId);

        expect($claim->email)->toBe('ada@example.test');
        expect(evo()->lastMail()['to'])->toBe('ada@example.test');
        expect(currentEmail($userId))->not->toBe('ada@example.test');
        expect(EmailVerifier::isVerified($userId))->toBeFalse();

        $result = EmailVerifier::confirm($claim->token);

        expect($result)->toBe(['user_id' => $userId, 'email' => 'ada@example.test']);
        expect(currentEmail($userId))->toBe('ada@example.test');
        expect(EmailVerifier::isVerified($userId))->toBeTrue();
        expect(EmailVerifier::pendingFor($userId))->toBeNull();
    });

    it('mails a link carrying the claim token', function () {
        $userId = makeUser(['email' => 'old@example.test']);

        EmailVerifier::request($userId, 'new@example.test', Config::CONTEXT_WEB);

        $token = EmailVerifier::pendingFor($userId)->token;

        expect(evo()->lastMail()['body'])->toContain(Config::verifyUrl($token));
    });

    it('makes the account promotable once the address is proven', function () {
        // The point of the whole exercise: a manager needs a reachable address
        // because password recovery is the way back in.
        $tg     = insertProvider('telegram');
        $userId = makeUser(['email' => UserResolver::placeholderEmail($tg, 'tg-9'), 'verified' => 0]);

        expect(\EvolutionCMS\aSocialAuth\Support\Promotion::canBecomeManager($userId))->toBeFalse();

        EmailVerifier::request($userId, 'ada@example.test', Config::CONTEXT_WEB);
        EmailVerifier::confirm(EmailVerifier::pendingFor($userId)->token);

        expect(\EvolutionCMS\aSocialAuth\Support\Promotion::canBecomeManager($userId))->toBeTrue();
    });

    it('lets the proven address then attract that person\'s other providers', function () {
        // Verification is what unblocks match_by_email for this account.
        $tg     = insertProvider('telegram');
        $google = insertProvider('google');
        $userId = makeUser(['email' => UserResolver::placeholderEmail($tg, 'tg-9'), 'verified' => 0]);

        EmailVerifier::request($userId, 'ada@example.test', Config::CONTEXT_WEB);
        EmailVerifier::confirm(EmailVerifier::pendingFor($userId)->token);

        $result = UserResolver::forLogin($google, makeProfile([
            'identifier'    => 'g-1',
            'email'         => 'ada@example.test',
            'emailVerified' => 'ada@example.test',
        ]));

        expect($result['user_id'])->toBe($userId);
    });

    it('replaces an outstanding claim rather than keeping both', function () {
        $userId = makeUser();

        EmailVerifier::request($userId, 'first@example.test', Config::CONTEXT_WEB);
        $stale = EmailVerifier::pendingFor($userId)->token;

        EmailVerifier::request($userId, 'second@example.test', Config::CONTEXT_WEB);

        expect(EmailVerifier::pendingFor($userId)->email)->toBe('second@example.test');

        // The abandoned link must be dead, or a mistyped address could still be
        // confirmed later from an old message.
        expect(fn () => EmailVerifier::confirm($stale))->toThrow(SocialAuthException::class);
    });

    it('refuses an address another account already holds', function () {
        makeUser(['email' => 'taken@example.test']);
        $userId = makeUser(['email' => 'mine@example.test']);

        expect(fn () => EmailVerifier::request($userId, 'taken@example.test', Config::CONTEXT_WEB))
            ->toThrow(SocialAuthException::class, __('aSocialAuth::login.error_email_taken'));
    });

    it('refuses an address the user already has', function () {
        $userId = makeUser(['email' => 'mine@example.test']);

        expect(fn () => EmailVerifier::request($userId, 'mine@example.test', Config::CONTEXT_WEB))
            ->toThrow(SocialAuthException::class, __('aSocialAuth::login.error_email_already_yours'));
    });

    it('rechecks ownership at confirmation, not only at claim time', function () {
        // Two people may claim the same address; only the first to confirm gets
        // it, and the e-mail column is unique.
        $ada = makeUser(['email' => 'ada@example.test']);
        $bob = makeUser(['email' => 'bob@example.test']);

        EmailVerifier::request($bob, 'shared@example.test', Config::CONTEXT_WEB);
        $bobToken = EmailVerifier::pendingFor($bob)->token;

        EmailVerifier::request($ada, 'shared@example.test', Config::CONTEXT_WEB);
        EmailVerifier::confirm(EmailVerifier::pendingFor($ada)->token);

        expect(fn () => EmailVerifier::confirm($bobToken))
            ->toThrow(SocialAuthException::class, __('aSocialAuth::login.error_email_taken'));

        expect(currentEmail($ada))->toBe('shared@example.test');
        expect(currentEmail($bob))->toBe('bob@example.test');
    });

    it('rejects an expired claim and clears it', function () {
        $userId = makeUser();

        EmailVerifier::request($userId, 'ada@example.test', Config::CONTEXT_WEB);
        $token = EmailVerifier::pendingFor($userId)->token;

        EmailVerification::query()->where('token', $token)
            ->update(['expires_at' => date('Y-m-d H:i:s', time() - 60)]);

        expect(fn () => EmailVerifier::confirm($token))
            ->toThrow(SocialAuthException::class, __('aSocialAuth::login.error_verify_token'));

        expect(EmailVerification::findByToken($token))->toBeNull();
    });

    it('rejects a token that was never issued', function () {
        expect(fn () => EmailVerifier::confirm('nope'))
            ->toThrow(SocialAuthException::class, __('aSocialAuth::login.error_verify_token'));

        expect(fn () => EmailVerifier::confirm(''))
            ->toThrow(SocialAuthException::class);
    });

    it('reports a claim that could not be mailed instead of leaving the user waiting', function () {
        $userId = makeUser();
        evo()->mailWorks = false;

        expect(fn () => EmailVerifier::request($userId, 'ada@example.test', Config::CONTEXT_WEB))
            ->toThrow(SocialAuthException::class, __('aSocialAuth::login.error_email_send'));

        // Nothing was proven and nothing changed on the account.
        expect(currentEmail($userId))->not->toBe('ada@example.test');
    });

    it('refuses a malformed address before touching anything', function () {
        $userId = makeUser();

        expect(fn () => EmailVerifier::request($userId, 'not-an-address', Config::CONTEXT_WEB))
            ->toThrow(SocialAuthException::class, __('aSocialAuth::login.error_email_invalid'));

        expect(EmailVerifier::pendingFor($userId))->toBeNull();
    });

    it('refuses a claim inside the placeholder domain', function () {
        $userId = makeUser();

        expect(fn () => EmailVerifier::request($userId, 'fake-abc@social.invalid', Config::CONTEXT_WEB))
            ->toThrow(SocialAuthException::class, __('aSocialAuth::login.error_email_invalid'));
    });

    it('can be withdrawn', function () {
        $userId = makeUser();

        EmailVerifier::request($userId, 'ada@example.test', Config::CONTEXT_WEB);
        EmailVerifier::cancel($userId);

        expect(EmailVerifier::pendingFor($userId))->toBeNull();
    });

    it('treats an expired claim as no claim at all', function () {
        $userId = makeUser();

        EmailVerifier::request($userId, 'ada@example.test', Config::CONTEXT_WEB);

        EmailVerification::query()->where('user_id', $userId)
            ->update(['expires_at' => date('Y-m-d H:i:s', time() - 60)]);

        expect(EmailVerifier::pendingFor($userId))->toBeNull();
    });
});

it('degrades quietly when the claims table is unreachable', function () {
    // Migrations not run yet: a profile page must still render.
    \Illuminate\Database\Capsule\Manager::schema()->drop('social_email_verifications');

    expect(EmailVerifier::pendingFor(1))->toBeNull();

    EmailVerifier::cancel(1);
})->throwsNoExceptions();
