<?php

use EvolutionCMS\aSocialAuth\Support\Config;
use EvolutionCMS\aSocialAuth\Support\Identity;

/**
 * Reading the current session.
 *
 * Evolution CMS keeps the manager and web sessions side by side —
 * `mgrValidated`/`mgrInternalKey` and `webValidated`/`webInternalKey` — and a
 * visitor may hold both at once, so every question here is asked per context.
 */

it('reports nobody when the session is empty', function () {
    expect(Identity::currentUserId(Config::CONTEXT_MGR))->toBeNull();
    expect(Identity::currentUserId(Config::CONTEXT_WEB))->toBeNull();
    expect(Identity::anyCurrentUserId())->toBeNull();
    expect(Identity::currentContext())->toBeNull();
});

it('reads the signed-in user for each context independently', function () {
    $_SESSION['webValidated']   = 1;
    $_SESSION['webInternalKey'] = 12;

    expect(Identity::currentUserId(Config::CONTEXT_WEB))->toBe(12);
    expect(Identity::currentUserId(Config::CONTEXT_MGR))->toBeNull();
    expect(Identity::currentContext())->toBe('web');
});

it('prefers the manager session when a visitor holds both', function () {
    // Someone holding both is an administrator looking at the manager, and that
    // is the account the identities widget should operate on.
    $_SESSION['mgrValidated']   = 1;
    $_SESSION['mgrInternalKey'] = 1;
    $_SESSION['webValidated']   = 1;
    $_SESSION['webInternalKey'] = 12;

    expect(Identity::anyCurrentUserId())->toBe(1);
    expect(Identity::currentContext())->toBe('mgr');
});

it('ignores an internal key with no validation flag beside it', function () {
    // A key left behind by a destroyed session must not read as a live login.
    $_SESSION['webInternalKey'] = 12;

    expect(Identity::currentUserId(Config::CONTEXT_WEB))->toBeNull();
});

it('ignores a validated flag with no usable key', function () {
    $_SESSION['webValidated']   = 1;
    $_SESSION['webInternalKey'] = 0;

    expect(Identity::currentUserId(Config::CONTEXT_WEB))->toBeNull();
});

it('treats a falsy validation flag as signed out', function () {
    $_SESSION['webValidated']   = 0;
    $_SESSION['webInternalKey'] = 12;

    expect(Identity::currentUserId(Config::CONTEXT_WEB))->toBeNull();
});

it('survives a session that was never started', function () {
    unset($_SESSION);

    expect(Identity::currentUserId(Config::CONTEXT_WEB))->toBeNull();

    $_SESSION = [];
});

it('assumes an identity is the last one when it cannot count them', function () {
    // Refusing to unlink is recoverable; locking someone out of their own
    // account is not, so an unreadable database resolves the safe way.
    $_SESSION['webValidated']   = 1;
    $_SESSION['webInternalKey'] = 12;

    expect(Identity::isLastIdentity(12))->toBeTrue();
});

it('returns an empty list rather than failing when accounts cannot be read', function () {
    expect(Identity::linkedAccounts(12)->all())->toBe([]);
    expect(Identity::linkedSlugs(12))->toBe([]);
});
