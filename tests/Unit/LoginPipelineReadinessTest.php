<?php

use Elcreator\aSocialAuth\Exceptions\ChallengeRequiredException;
use Elcreator\aSocialAuth\Exceptions\SocialAuthException;
use Elcreator\aSocialAuth\Support\PendingLogin;
use Elcreator\aSocialAuth\Support\SocialAuth;
use EvolutionCMS\Exceptions\ServiceActionException;

/**
 * Readiness for whatever a site puts in the core's login pipeline.
 *
 * The pipeline is the mechanism a captcha, a TOTP check or a site's own rule
 * plugs into. Three things have to hold for social login to survive that:
 *
 *   1. social sign-ins go *through* the pipeline, not around it — otherwise
 *      enabling this package would quietly become a way to bypass 2FA;
 *   2. a pipe can tell a social attempt from a form post, because some checks
 *      (a captcha) are meaningless on an OAuth callback and others (a second
 *      factor) need to behave differently;
 *   3. a pipe that needs to *ask* the visitor something can hand control to a
 *      page instead of dead-ending, because a callback has no form to read.
 */

it('signs in through loginById so every pipe on that stage runs', function () {
    SocialAuth::signIn(7, 'mgr', 'google', 'g-123');

    expect(UserManager::$logins)->toHaveCount(1);
    expect(UserManager::lastLogin())->toMatchArray([
        'id'      => 7,
        'context' => 'mgr',
    ]);
});

it('marks the attempt so a pipe can recognise a social login', function () {
    SocialAuth::signIn(7, 'web', 'telegram', 'tg-9');

    $payload = UserManager::lastLogin();

    // A captcha pipe reads these to skip: the provider has already done the
    // human check, and there is no form to render a challenge into.
    expect($payload['social'])->toBeTrue();
    expect($payload['social_provider'])->toBe('telegram');
    expect($payload['social_provider_user_id'])->toBe('tg-9');
});

it('merges extra data a pipe asked for into the retried attempt', function () {
    SocialAuth::signIn(7, 'web', 'google', 'g-1', ['totp' => '123456']);

    expect(UserManager::lastLogin()['totp'])->toBe('123456');
});

it('lets extra data override the defaults', function () {
    SocialAuth::signIn(7, 'web', 'google', 'g-1', ['rememberme' => true]);

    expect(UserManager::lastLogin()['rememberme'])->toBeTrue();
});

it('turns a pipe refusal into a message for the visitor', function () {
    UserManager::$throw = new ServiceActionException('second factor required');

    expect(fn () => SocialAuth::signIn(7, 'mgr', 'google'))
        ->toThrow(SocialAuthException::class, 'second factor required');
});

it('does not leak an unexpected failure to the visitor', function () {
    UserManager::$throw = new RuntimeException('SQLSTATE[HY000] connection refused');

    try {
        SocialAuth::signIn(7, 'mgr', 'google');
        $this->fail('expected a SocialAuthException');
    } catch (SocialAuthException $e) {
        expect($e->getMessage())->not->toContain('SQLSTATE');
        expect($e->getMessage())->toBe(__('aSocialAuth::login.error_login'));
    }
});

describe('interactive challenges', function () {
    it('propagates a challenge rather than swallowing it', function () {
        UserManager::$throw = ChallengeRequiredException::at('/two-factor', 'totp');

        expect(fn () => SocialAuth::signIn(7, 'web', 'google'))
            ->toThrow(ChallengeRequiredException::class);
    });

    it('carries the destination and the challenge name', function () {
        $exception = ChallengeRequiredException::at('/two-factor', 'totp', ['digits' => 6]);

        expect($exception->redirectPath())->toBe('/two-factor');
        expect($exception->challenge())->toBe('totp');
        expect($exception->payload())->toBe(['digits' => 6]);
    });

    it('completes a parked login once the factor arrives', function () {
        PendingLogin::store(11, 'web', 'google', '/members');

        $destination = SocialAuth::completePendingLogin(['totp' => '123456']);

        expect(UserManager::lastLogin())->toMatchArray([
            'id'     => 11,
            'totp'   => '123456',
            'social' => true,
        ]);
        expect($destination)->toBe('https://example.test/members');
        expect(PendingLogin::exists())->toBeFalse();
    });

    it('sends the visitor to the context default when no return was captured', function () {
        PendingLogin::store(11, 'web', 'google', null);

        expect(SocialAuth::completePendingLogin())->toBe('https://example.test/');
    });

    it('refuses to complete when nothing is pending', function () {
        expect(fn () => SocialAuth::completePendingLogin(['totp' => '1']))
            ->toThrow(SocialAuthException::class);
    });

    it('discards the pending login when the factor is wrong', function () {
        PendingLogin::store(11, 'web', 'google', null);
        UserManager::$throw = new ServiceActionException('bad code');

        expect(fn () => SocialAuth::completePendingLogin(['totp' => '000000']))
            ->toThrow(SocialAuthException::class);

        expect(PendingLogin::exists())->toBeFalse();
    });

    it('can keep the pending login so a page may offer a retry', function () {
        PendingLogin::store(11, 'web', 'google', null);
        UserManager::$throw = new ServiceActionException('bad code');

        try {
            SocialAuth::completePendingLogin(['totp' => '000000'], keepOnFailure: true);
        } catch (SocialAuthException $e) {
            // expected
        }

        expect(PendingLogin::exists())->toBeTrue();
    });

    it('never signs anyone in while a challenge is outstanding', function () {
        PendingLogin::store(11, 'web', 'google', null);

        // The parked state is a note, not a session: nothing in it is a
        // credential and no login has been performed.
        expect(UserManager::$logins)->toBeEmpty();
        expect($_SESSION['webValidated'] ?? null)->toBeNull();
    });

    it('expires a challenge that was never answered', function () {
        PendingLogin::store(11, 'web', 'google', null);

        $_SESSION[PendingLogin::KEY]['started'] = time() - PendingLogin::TTL_SECONDS - 1;

        expect(PendingLogin::peek())->toBeNull();
        expect(PendingLogin::exists())->toBeFalse();
    });
});
