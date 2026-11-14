<?php

use Elcreator\aSocialAuth\Exceptions\SocialAuthException;
use Elcreator\aSocialAuth\Support\Config;
use Elcreator\aSocialAuth\Support\PendingLogin;
use Elcreator\aSocialAuth\Support\SocialAuth;
use EvolutionCMS\Models\UserAttribute;

beforeEach(fn () => bootTestDatabase());

it('defaults missing and invalid context to web even with a manager referer', function () {
    expect(Config::configuredContext())->toBe('web');
    unset(TestConfig::$items['cms']['settings']['aSocialAuth']['context']);
    expect(Config::resolveContext('https://example.test/manager/'))->toBe('web');
    TestConfig::set('cms.settings.aSocialAuth.context', 'invalid');
    expect(Config::resolveContext('https://example.test/manager/'))->toBe('web');
});

it('refuses role zero for explicitly requested and referer selected manager logins', function ($setting) {
    TestConfig::set('cms.settings.aSocialAuth.context', $setting);
    $id = makeUser();
    $context = Config::resolveContext('https://example.test/manager/');
    expect(fn () => SocialAuth::signIn($id, $context, 'google'))->toThrow(SocialAuthException::class);
    expect(UserManager::$logins)->toBeEmpty();
    expect($_SESSION['mgrValidated'] ?? null)->toBeNull();
})->with(['mgr', 'auto']);

it('allows role zero to sign in to the web', function () {
    $id = makeUser();
    SocialAuth::signIn($id, 'web', 'google');
    expect(UserManager::lastLogin()['context'])->toBe('web');
});

it('allows only existing roles granting manager access', function ($role, $permission, $allowed) {
    $id = makeUser(['role' => $role]);
    if ($role !== 99) {
        \EvolutionCMS\Models\UserRole::query()->insert(['id' => $role, 'access_permissions' => 0]);
    }
    if ($permission) {
        \EvolutionCMS\Models\RolePermissions::query()->insert(['role_id' => $role, 'permission' => 'access_permissions']);
    }
    if ($allowed) {
        SocialAuth::signIn($id, 'mgr', 'google');
        expect(UserManager::lastLogin()['context'])->toBe('mgr');
    } else {
        expect(fn () => SocialAuth::signIn($id, 'mgr', 'google'))->toThrow(SocialAuthException::class);
        expect(UserManager::$logins)->toBeEmpty();
    }
})->with([[1, false, true], [2, true, true], [3, false, false], [99, true, false]]);

it('rechecks manager access after a challenge and role revocation', function () {
    $id = makeUser(['role' => 1]);
    PendingLogin::store($id, 'mgr', 'google', null);
    UserAttribute::query()->where('internalKey', $id)->update(['role' => 0]);
    expect(fn () => SocialAuth::completePendingLogin())->toThrow(SocialAuthException::class);
    expect(UserManager::$logins)->toBeEmpty();
    expect(PendingLogin::exists())->toBeFalse();
});

it('does not let challenge extras replace the authorized user or context', function () {
    $id = makeUser();
    SocialAuth::signIn($id, 'web', 'google', '', ['id' => 1, 'context' => 'mgr']);
    expect(UserManager::lastLogin())->toMatchArray(['id' => $id, 'context' => 'web']);
});

it('fails closed for a missing manager user or an invalid context', function () {
    expect(fn () => SocialAuth::signIn(999, 'mgr', 'google'))->toThrow(SocialAuthException::class);
    expect(fn () => SocialAuth::signIn(makeUser(), 'invalid', 'google'))->toThrow(SocialAuthException::class);
    expect(UserManager::$logins)->toBeEmpty();
});
