<?php

use Elcreator\aSocialAuth\Enums\SocialProvider;
use Elcreator\aSocialAuth\Exceptions\ChallengeRequiredException;
use Elcreator\aSocialAuth\Exceptions\SocialAuthException;
use Elcreator\aSocialAuth\Http\Controllers\OneTapController;
use Elcreator\aSocialAuth\Models\SocialAccount;
use Elcreator\aSocialAuth\Support\Config;
use Elcreator\aSocialAuth\Support\FlowState;
use Elcreator\aSocialAuth\Support\IdTokenVerifier;
use Elcreator\aSocialAuth\Support\OneTap;
use Elcreator\aSocialAuth\Support\PendingLogin;
use Elcreator\aSocialAuth\Support\Renderer;
use Elcreator\aSocialAuth\Support\SocialAuth;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * One Tap, with real signatures: the token comes through the visitor's browser,
 * so everything rests on what the verifier refuses.
 */

const ONE_TAP_CLIENT = 'client-1.apps.googleusercontent.com';

/** @return array{private: string, jwk: array} */
function oneTapKey(string $name = 'main'): array
{
    static $keys = [];

    if (isset($keys[$name])) {
        return $keys[$name];
    }

    // PHP on Windows has no default openssl.cnf to fall back on.
    $config = tempnam(sys_get_temp_dir(), 'asa');
    file_put_contents($config, "[req]\ndistinguished_name = dn\n[dn]\n");
    $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'config' => $config];

    $key = openssl_pkey_new($options);
    openssl_pkey_export($key, $private, null, $options);
    $rsa = openssl_pkey_get_details($key)['rsa'];
    unlink($config);

    return $keys[$name] = [
        'private' => $private,
        'jwk'     => [
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => $name,
            'n'   => JWT::urlsafeB64Encode($rsa['n']),
            'e'   => JWT::urlsafeB64Encode($rsa['e']),
        ],
    ];
}

function oneTapClaims(array $override = []): array
{
    return array_filter(array_merge([
        'iss'            => 'https://accounts.google.com',
        'aud'            => ONE_TAP_CLIENT,
        'sub'            => 'google-sub-1',
        'nonce'          => 'nonce-1',
        'email'          => 'ada@example.test',
        'email_verified' => true,
        'name'           => 'Ada Lovelace',
        'given_name'     => 'Ada',
        'family_name'    => 'Lovelace',
        'picture'        => 'https://img.test/ada.png',
        'iat'            => time() - 5,
        'exp'            => time() + 3600,
    ], $override), static fn ($value) => $value !== null);
}

function oneTapToken(array $override = [], string $key = 'main', ?string $kid = null): string
{
    return JWT::encode(oneTapClaims($override), oneTapKey($key)['private'], 'RS256', $kid ?? $key);
}

function verifyOneTap(string $token, ?string $nonce = 'nonce-1'): \Hybridauth\User\Profile
{
    return IdTokenVerifier::verify($token, SocialProvider::GOOGLE->oneTap(), ONE_TAP_CLIENT, $nonce);
}

function enableOneTap(): void
{
    TestConfig::set('cms.settings.aSocialAuth.enable', true);
    TestConfig::set('cms.settings.aSocialAuth.providers.google.enabled', true);
    TestConfig::set('cms.settings.aSocialAuth.providers.google.keys', ['id' => ONE_TAP_CLIENT, 'secret' => 's']);
    TestConfig::set('cms.settings.aSocialAuth.providers.google.one_tap', ['enabled' => true, 'auto_select' => true]);

    $_SESSION['_token'] = 'csrf';
}

function postOneTap(array $input, string $provider = 'google'): \Illuminate\Http\RedirectResponse
{
    return (new OneTapController())(Request::create('/asocialauth/google/onetap', 'POST', $input), $provider);
}

beforeEach(function () {
    OneTap::reset();
    unset($_COOKIE[OneTap::SIGNED_OUT_COOKIE]);

    IdTokenVerifier::$fetcher = static fn (string $url) => [
        'body'    => json_encode(['keys' => [oneTapKey()['jwk']]]),
        'max_age' => 3600,
    ];
});

afterEach(function () {
    IdTokenVerifier::$fetcher = null;
});

describe('ID token verification', function () {
    it('reads a genuine token the way HybridAuth reads userinfo', function () {
        $profile = verifyOneTap(oneTapToken());

        expect($profile->identifier)->toBe('google-sub-1')
            ->and($profile->displayName)->toBe('Ada Lovelace')
            ->and($profile->firstName)->toBe('Ada')
            ->and($profile->lastName)->toBe('Lovelace')
            ->and($profile->photoURL)->toBe('https://img.test/ada.png')
            ->and($profile->email)->toBe('ada@example.test')
            ->and($profile->emailVerified)->toBe('ada@example.test');
    });

    it('accepts the issuer with and without a scheme', function () {
        expect(verifyOneTap(oneTapToken(['iss' => 'accounts.google.com']))->identifier)->toBe('google-sub-1');
    });

    it('does not call an address verified unless the provider does', function ($claim) {
        expect(verifyOneTap(oneTapToken(['email_verified' => $claim]))->emailVerified)->toBe('');
    })->with([false, 'false', null]);

    it('refuses claims it cannot trust', function (array $claims) {
        verifyOneTap(oneTapToken($claims));
    })->with([
        'foreign issuer'     => [['iss' => 'https://evil.test']],
        'another client'     => [['aud' => 'other.apps.googleusercontent.com']],
        'several audiences'  => [['aud' => [ONE_TAP_CLIENT, 'other']]],
        'expired'            => [['exp' => time() - 3600]],
        'no expiry'          => [['exp' => null]],
        'issued in future'   => [['iat' => time() + 3600]],
        'foreign nonce'      => [['nonce' => 'nonce-2']],
        'no nonce'           => [['nonce' => null]],
        'no subject'         => [['sub' => null]],
        'empty subject'      => [['sub' => '']],
    ])->throws(SocialAuthException::class);

    it('refuses a token when the session issued no nonce', function () {
        verifyOneTap(oneTapToken(), null);
    })->throws(SocialAuthException::class);

    it('refuses a signature from another key under a known key id', function () {
        verifyOneTap(oneTapToken([], 'attacker', 'main'));
    })->throws(SocialAuthException::class);

    it('refuses an unknown key id', function () {
        verifyOneTap(oneTapToken([], 'attacker'));
    })->throws(SocialAuthException::class);

    it('never lets the token choose its algorithm', function (string $token) {
        verifyOneTap($token);
    })->with([
        'none' => fn () => JWT::urlsafeB64Encode('{"alg":"none","kid":"main"}')
            . '.' . JWT::urlsafeB64Encode(json_encode(oneTapClaims())) . '.',
        // HMAC keyed with the public modulus: the classic confusion attack.
        'HS256' => fn () => JWT::encode(oneTapClaims(), oneTapKey()['jwk']['n'], 'HS256', 'main'),
    ])->throws(SocialAuthException::class);

    it('refuses what is not a token at all', function (string $token) {
        verifyOneTap($token);
    })->with(['', 'a.b', 'a.b.c', 'a.b.c.d'])->throws(SocialAuthException::class);

    it('refuses everything when the keys cannot be loaded', function () {
        IdTokenVerifier::$fetcher = static fn () => ['body' => '<html>', 'max_age' => null];

        verifyOneTap(oneTapToken());
    })->throws(SocialAuthException::class);
});

describe('when the prompt is offered', function () {
    it('is off until the site asks for it', function () {
        enableOneTap();
        TestConfig::set('cms.settings.aSocialAuth.providers.google.one_tap', null);

        expect(OneTap::viewData())->toBeNull();
    });

    it('carries what the script needs, and not the secret', function () {
        enableOneTap();

        $data = OneTap::viewData('/members');

        expect($data['clientId'])->toBe(ONE_TAP_CLIENT)
            ->and($data['nonce'])->toBe(OneTap::nonce())
            ->and($data['autoSelect'])->toBeTrue()
            ->and($data['action'])->toBe('https://example.test/asocialauth/google/onetap')
            ->and($data['token'])->toBe('csrf')
            ->and($data['returnPath'])->toBe('/members')
            ->and($data)->not->toContain('s');
    });

    it('prompts once per page', function () {
        enableOneTap();

        expect(OneTap::viewData())->not->toBeNull()
            ->and(OneTap::viewData())->toBeNull();
    });

    it('never opens manager sessions', function () {
        enableOneTap();
        TestConfig::set('cms.settings.aSocialAuth.context', 'mgr');

        expect(OneTap::viewData())->toBeNull();
        postOneTap(['credential' => oneTapToken()]);
    })->throws(HttpException::class);

    it('leaves alone a visitor who is signed in, challenged or just refused', function (string $state) {
        enableOneTap();

        match ($state) {
            'web', 'mgr' => $_SESSION += [$state . 'Validated' => 1, $state . 'InternalKey' => 5],
            'challenged' => PendingLogin::store(5, 'web', 'google', null, 'totp'),
            'refused'    => OneTap::suppress(),
        };

        expect(OneTap::viewData())->toBeNull();
    })->with(['web', 'mgr', 'challenged', 'refused']);

    it('can be shown without automatic sign-in', function () {
        enableOneTap();
        TestConfig::set('cms.settings.aSocialAuth.providers.google.one_tap.auto_select', false);

        expect(OneTap::viewData()['autoSelect'])->toBeFalse();
    });

    it('moves with a custom route pattern', function () {
        TestConfig::set('cms.settings.aSocialAuth.routes.prefix', 'auth');
        TestConfig::set('cms.settings.aSocialAuth.routes.patterns.onetap', 'in/{provider}/tap');

        expect(Config::buildOneTapUrl('google'))->toBe('https://example.test/auth/in/google/tap');
    });

    it('renders nothing when the package is off', function () {
        enableOneTap();
        TestConfig::set('cms.settings.aSocialAuth.enable', false);

        expect(Renderer::oneTapWidget())->toBe('');
    });
});

describe('signing out', function () {
    it('holds automatic sign-in back until the next sign-in', function () {
        enableOneTap();
        OneTap::rememberSignOut();

        $data = OneTap::viewData();
        expect($data['autoSelect'])->toBeFalse()
            ->and($data['signedOut'])->toBeTrue();

        SocialAuth::signIn(5, 'web', 'google');
        expect(OneTap::hasSignedOut())->toBeFalse();
    });

    it('listens to both logout events', function () {
        $plugin = file_get_contents(__DIR__ . '/../../plugins/aSocialAuthPlugin.php');

        expect($plugin)->toContain("['OnManagerLogout', 'OnWebLogout']");
    });
});

describe('redeeming the token', function () {
    beforeEach(function () {
        enableOneTap();
        $_SESSION[OneTap::NONCE_KEY] = 'nonce-1';
    });

    it('signs in on the identity the button would use, through the login pipeline', function () {
        $userId = makeUser(['email' => 'ada@example.test']);

        $response = postOneTap(['credential' => oneTapToken(), 'return' => '/members']);

        expect($response->getTargetUrl())->toBe('https://example.test/members')
            ->and(UserManager::lastLogin())->toMatchArray([
                'id'                      => $userId,
                'context'                 => 'web',
                'social'                  => true,
                'social_provider'         => 'google',
                'social_provider_user_id' => 'google-sub-1',
            ])
            ->and(SocialAccount::query()->first()->provider->slug)->toBe('google');
    });

    it('stays on the page the prompt was on', function () {
        makeUser(['email' => 'ada@example.test']);

        $request = Request::create('/asocialauth/google/onetap', 'POST', ['credential' => oneTapToken()]);
        $request->headers->set('referer', 'https://example.test/blog/post?x=1');

        expect((new OneTapController())($request, 'google')->getTargetUrl())
            ->toBe('https://example.test/blog/post?x=1');
    });

    it('opens a web session even from a manager referer', function () {
        TestConfig::set('cms.settings.aSocialAuth.context', 'auto');
        makeUser(['email' => 'ada@example.test', 'role' => 1]);

        $request = Request::create('/asocialauth/google/onetap', 'POST', ['credential' => oneTapToken()]);
        $request->headers->set('referer', 'https://example.test/manager/');
        (new OneTapController())($request, 'google');

        expect(UserManager::lastLogin()['context'])->toBe('web');
    });

    it('redeems a token once', function () {
        makeUser(['email' => 'ada@example.test']);
        $token = oneTapToken();

        postOneTap(['credential' => $token]);
        postOneTap(['credential' => $token]);

        expect(UserManager::$logins)->toHaveCount(1)
            ->and(FlowState::takeError())->toBe(__('aSocialAuth::login.error_expired'));
    });

    it('goes back with a message and stops prompting when nobody matches', function () {
        $response = postOneTap(['credential' => oneTapToken(), 'return' => '/blog']);

        expect($response->getTargetUrl())->toBe('https://example.test/blog')
            ->and(UserManager::$logins)->toBe([])
            ->and(FlowState::takeError())->toBe(__('aSocialAuth::login.error_no_user'))
            ->and(OneTap::viewData())->toBeNull();
    });

    it('survives a credential that is not a string', function () {
        postOneTap(['credential' => ['x' => 'y']]);

        expect(UserManager::$logins)->toBe([]);
    });

    it('pauses for a challenge like any other social sign-in', function () {
        $userId = makeUser(['email' => 'ada@example.test']);
        UserManager::$throw = ChallengeRequiredException::at('/two-factor', 'totp');

        $response = postOneTap(['credential' => oneTapToken(), 'return' => '/members']);

        expect($response->getTargetUrl())->toBe('https://example.test/two-factor')
            ->and(PendingLogin::peek())->toMatchArray(['user_id' => $userId, 'return' => '/members']);
    });

    it('never swaps the account of someone already signed in', function () {
        makeUser(['email' => 'ada@example.test']);
        $_SESSION['webValidated']   = 1;
        $_SESSION['webInternalKey'] = 99;

        postOneTap(['credential' => oneTapToken()]);

        expect(UserManager::$logins)->toBe([]);
    });

    it('is absent for a provider without One Tap', function () {
        TestConfig::set('cms.settings.aSocialAuth.providers.facebook.enabled', true);
        TestConfig::set('cms.settings.aSocialAuth.providers.facebook.keys', ['id' => 'a', 'secret' => 'b']);
        TestConfig::set('cms.settings.aSocialAuth.providers.facebook.one_tap', ['enabled' => true]);

        postOneTap(['credential' => oneTapToken()], 'facebook');
    })->throws(HttpException::class);
});
