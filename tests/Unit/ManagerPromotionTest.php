<?php

use EvolutionCMS\aSocialAuth\Support\Config;
use EvolutionCMS\aSocialAuth\Support\Promotion;
use EvolutionCMS\aSocialAuth\Support\UserResolver;

/**
 * Can an administrator give manager rights to someone who signs in socially?
 *
 * Yes — and these tests pin down the two halves of that answer.
 *
 * The mechanism is core, not this package: Evolution CMS 3.x keeps manager users
 * and web users in one `users` table and separates them only by
 * `user_attributes.role`, so raising a social account is the same operation as
 * raising any other. The administrator opens it under Users → Web user
 * management, picks a role, and saves; the form calls \UserManager::setRole(),
 * which is the single door role changes go through — `role` is deliberately
 * absent from UserAttribute's mass-assignable attributes and skipped by UserEdit,
 * so nothing else can set it. It requires the `save_role` permission.
 *
 * The precondition is the second half: a manager account needs an e-mail address
 * that actually receives mail, because password recovery is the way back in. An
 * account created through Telegram or Steam has a synthesised address that never
 * will.
 */

it('creates social accounts as plain web users, never as managers', function () {
    // Role 0 is "no user role": a web user with no manager access at all. Any
    // other default would hand manager standing to whoever completes an OAuth
    // flow with an enabled provider.
    expect(Config::defaultRole())->toBe(0);
    expect(Promotion::isManagerRole(Config::defaultRole()))->toBeFalse();
});

it('treats any non-zero role as a promotion out of web-user standing', function () {
    expect(Promotion::isManagerRole(0))->toBeFalse();
    expect(Promotion::isManagerRole(1))->toBeTrue();
    expect(Promotion::isManagerRole(3))->toBeTrue();
});

it('still refuses to let a configured role be applied by mass assignment', function () {
    // The core keeps `role` out of the fillable attributes precisely so that a
    // privilege change cannot ride along on an ordinary user save. If that ever
    // changed, this package's create path would silently start granting roles.
    $fillable = (new \EvolutionCMS\Models\UserAttribute())->getFillable();

    expect($fillable)->not->toContain('role');
});

describe('the real-address precondition', function () {
    it('reports a placeholder address as unfit for a manager role', function () {
        $email = UserResolver::placeholderEmail(makeProvider('telegram'), '12345');

        expect(UserResolver::isPlaceholderEmail($email))->toBeTrue();

        // An address at the reserved .invalid TLD can never receive mail, so a
        // manager account holding one has no password-recovery route at all.
        expect($email)->toEndWith('@social.invalid');
    });

    it('accepts an address a provider actually supplied', function () {
        expect(UserResolver::isPlaceholderEmail('ada@example.test'))->toBeFalse();
    });

    it('explains the refusal in terms an administrator can act on', function () {
        $reason = __('aSocialAuth::login.promote_placeholder_email');

        expect($reason)->toContain('placeholder');
        expect($reason)->toContain('recovery');
    });

    it('has a distinct message for an account with no address at all', function () {
        expect(__('aSocialAuth::login.promote_no_email'))
            ->not->toBe(__('aSocialAuth::login.promote_placeholder_email'));
    });
});

describe('what decides whether an account is promotable', function () {
    it('turns on the response, not on which provider it came from', function () {
        // The same provider can produce either outcome — a Google user who
        // withheld the e-mail scope arrives without an address, and a future X
        // app approved for e-mail arrives with one. So the profile decides.
        $provider = makeProvider('telegram');

        $withEmail = makeProfile([
            'identifier'    => 'tg-1',
            'email'         => 'ada@example.test',
            'emailVerified' => 'ada@example.test',
        ]);

        $withoutEmail = makeProfile(['identifier' => 'tg-2']);

        expect(UserResolver::email($withEmail))->toBe('ada@example.test');
        expect(UserResolver::email($withoutEmail))->toBeNull();

        // Only the second needs a placeholder, and it is the missing address
        // that says so — nothing consulted the provider's name.
        expect(UserResolver::isPlaceholderEmail(
            UserResolver::placeholderEmail($provider, 'tg-2')
        ))->toBeTrue();
    });

    it('creates a promotable account when any provider supplies a verified address', function () {
        TestConfig::set('cms.settings.aSocialAuth.registration.enable', true);

        // Telegram normally returns nothing; if it ever returns a verified
        // address, that account is as promotable as a Google one.
        $provider = insertProvider('telegram');

        $result = UserResolver::forLogin($provider, makeProfile([
            'identifier'    => 'tg-1',
            'email'         => 'ada@example.test',
            'emailVerified' => 'ada@example.test',
            'displayName'   => 'Ada',
        ]));

        expect(Promotion::canBecomeManager($result['user_id']))->toBeTrue();
    });

    it('creates a non-promotable account when any provider supplies none', function () {
        TestConfig::set('cms.settings.aSocialAuth.registration.enable', true);

        // And a Google sign-in with no address is treated exactly like a
        // Telegram one — same rule, no special case.
        $provider = insertProvider('google');

        $result = UserResolver::forLogin($provider, makeProfile([
            'identifier'  => 'g-1',
            'displayName' => 'Ada',
        ]));

        expect(Promotion::canBecomeManager($result['user_id']))->toBeFalse();
        expect(Promotion::blockReason($result['user_id']))
            ->toBe(__('aSocialAuth::login.promote_placeholder_email'));
    });
});

it('prompts the owner to add a real address', function () {
    // The identities widget surfaces this, which is how a Telegram-registered
    // account becomes promotable without an administrator editing the database.
    expect(__('aSocialAuth::login.placeholder_email_notice'))->toContain('e-mail');
});

it('offers the account owner a way to set a password once they have an address', function () {
    // Recovery is what turns a real address into an actual way back in, so the
    // reset flow has to be reachable for a socially-created account.
    expect(Config::credentialsRecoveryEnabled())->toBeTrue();
});
