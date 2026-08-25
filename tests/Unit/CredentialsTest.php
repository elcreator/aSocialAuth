<?php

use Elcreator\aSocialAuth\Support\Config;

/**
 * The e-mail-and-password half of the widget: configuration, URL assembly and
 * the reset link the core does not build.
 */

it('offers sign-in by default and keeps sign-ups closed', function () {
    // A login widget must not turn on public registration merely by being
    // installed; recovery is on because a login form without it strands people.
    expect(Config::credentialsLoginEnabled())->toBeTrue();
    expect(Config::credentialsRegistrationEnabled())->toBeFalse();
    expect(Config::credentialsRecoveryEnabled())->toBeTrue();
});

it('accepts either an e-mail or a username by default', function () {
    expect(Config::loginField())->toBe('both');

    TestConfig::set('cms.settings.aSocialAuth.credentials.login_field', 'email');
    expect(Config::loginField())->toBe('email');

    TestConfig::set('cms.settings.aSocialAuth.credentials.login_field', 'nonsense');
    expect(Config::loginField())->toBe('both');
});

it('never lets the password floor drop below what the core enforces', function () {
    // UserRegistration validates min:6; a lower setting here would only produce
    // a rejection further down that the form could not explain.
    TestConfig::set('cms.settings.aSocialAuth.credentials.min_password_length', 3);
    expect(Config::minimumPasswordLength())->toBe(6);

    TestConfig::set('cms.settings.aSocialAuth.credentials.min_password_length', 12);
    expect(Config::minimumPasswordLength())->toBe(12);
});

describe('form action URLs', function () {
    it('builds them under the configured prefix', function () {
        expect(Config::credentialsUrl('login'))
            ->toBe('https://example.test/asocialauth/credentials/login');
        expect(Config::credentialsUrl('register'))
            ->toBe('https://example.test/asocialauth/credentials/register');
        expect(Config::credentialsUrl('recover'))
            ->toBe('https://example.test/asocialauth/credentials/recover');
        expect(Config::credentialsUrl('reset'))
            ->toBe('https://example.test/asocialauth/credentials/reset');
    });

    it('follows the prefix when it changes', function () {
        TestConfig::set('cms.settings.aSocialAuth.routes.prefix', 'auth');

        expect(Config::credentialsUrl('login'))->toBe('https://example.test/auth/credentials/login');
    });

    it('keeps the credential paths out of reach of the provider route', function () {
        // {provider} is constrained to [a-z0-9]+, which would match a bare
        // "login". Putting the credential forms under their own segment is what
        // stops the provider route from shadowing them.
        $pattern = Config::providerPattern();

        foreach (['login', 'register', 'recover', 'reset'] as $action) {
            $path = Config::routePattern('credentials_' . $action);

            expect($path)->toContain('/');
            expect((bool) preg_match('#^' . $pattern . '$#', $path))->toBeFalse();
        }
    });
});

describe('the reset link', function () {
    it('produces nothing until the site nominates a page to host the form', function () {
        // The core only ever builds a manager reset link and skips the mail on
        // the front end, so without a page there is nowhere to send anyone.
        expect(Config::resetPath())->toBe('');
        expect(Config::resetUrl(Config::CONTEXT_WEB, 'abc123'))->toBe('');
    });

    it('points at the configured page with the token attached', function () {
        TestConfig::set('cms.settings.aSocialAuth.credentials.reset_path', '/account/reset');

        expect(Config::resetUrl(Config::CONTEXT_WEB, 'abc123'))
            ->toBe('https://example.test/account/reset?hash=abc123');
    });

    it('appends to a page that already carries a query string', function () {
        TestConfig::set('cms.settings.aSocialAuth.credentials.reset_path', '/index.php?id=42');

        expect(Config::resetUrl(Config::CONTEXT_WEB, 'abc123'))
            ->toBe('https://example.test/index.php?id=42&hash=abc123');
    });

    it('escapes a token that would otherwise break the URL', function () {
        TestConfig::set('cms.settings.aSocialAuth.credentials.reset_path', '/reset');

        expect(Config::resetUrl(Config::CONTEXT_WEB, 'a b&c=d'))
            ->toBe('https://example.test/reset?hash=a%20b%26c%3Dd');
    });

    it('uses the token parameter the core also uses, so one page can serve both', function () {
        expect(Config::resetTokenParam())->toBe('hash');
    });

    it('resolves against the manager root for a manager reset', function () {
        TestConfig::set('cms.settings.aSocialAuth.credentials.reset_path', 'index.php?a=0');

        expect(Config::resetUrl(Config::CONTEXT_MGR, 'tok'))
            ->toBe('https://example.test/manager/index.php?a=0&hash=tok');
    });
});
