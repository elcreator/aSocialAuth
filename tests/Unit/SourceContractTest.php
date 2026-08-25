<?php

use Elcreator\aSocialAuth\Support\Config;

/**
 * Source-level invariants — the kind that survive refactoring only if something
 * checks them. Each one encodes a decision that is easy to undo by accident.
 */

function packageSources(string $subdirectory = 'src'): array
{
    $root  = realpath(__DIR__ . '/../../' . $subdirectory);
    $files = [];

    if ($root === false) {
        return [];
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['php'], true)) {
            $files[$file->getPathname()] = file_get_contents($file->getPathname());
        }
    }

    return $files;
}

it('never hardcodes the route prefix outside the config default', function () {
    // The prefix is configurable; a literal anywhere in the code is a URL that
    // would not move with it, and for a callback URL that means a broken OAuth
    // round trip that only shows up in production.
    foreach (packageSources() as $path => $source) {
        expect($source)->not->toContain(
            "'asocialauth/",
            "hardcoded route prefix in " . basename($path)
        );
        expect($source)->not->toContain(
            '"asocialauth/',
            'hardcoded route prefix in ' . basename($path)
        );
    }
});

it('builds the callback URL only through Config', function () {
    // Two places deriving the callback URL differently is how a provider ends up
    // rejecting the redirect_uri.
    $offenders = [];

    foreach (packageSources() as $path => $source) {
        if (basename($path) === 'Config.php') {
            continue;
        }

        if (preg_match('/[\'"]\/callback[\'"]/', $source)) {
            $offenders[] = basename($path);
        }
    }

    expect($offenders)->toBe([]);
});

it('routes every role change through setRole', function () {
    // `role` is not mass assignable in the core precisely so that a privilege
    // change has one door. Assigning it directly would bypass that.
    foreach (packageSources() as $path => $source) {
        expect($source)->not->toMatch('/->role\s*=/');
    }
});

it('never writes provider credentials to the registry table', function () {
    // A database dump is a routine artefact; OAuth secrets must not ride along.
    $registry = file_get_contents(__DIR__ . '/../../src/Support/ProviderRegistry.php');

    // The sync payload is assembled in one place; assert it carries no key
    // material.
    preg_match('/\$attributes = \[(.*?)\];/s', $registry, $matches);

    expect($matches[1] ?? '')->not->toContain('keys');
    expect($matches[1] ?? '')->not->toContain('secret');
});

it('exempts only the OAuth legs from CSRF', function () {
    $routes = file_get_contents(__DIR__ . '/../../src/Http/routes.php');

    // The callback cannot carry a token — it is a GET from a third party, and
    // HybridAuth's state parameter guards it. Everything that changes state from
    // a form we rendered must be POST and CSRF-checked.
    foreach (['credentials_login', 'credentials_register', 'credentials_recover', 'credentials_reset', 'unlink'] as $action) {
        expect($routes)->toContain("Config::routePattern('{$action}')");
    }

    // Every POST is CSRF-checked; the count is derived rather than hardcoded so
    // adding a route cannot quietly leave one unguarded.
    expect(substr_count($routes, '->middleware(VerifyRequestToken::class)'))
        ->toBe(substr_count($routes, 'Route::post('));

    // And specifically NOT the core's `csrf` alias, which skips verification
    // outright when there is no manager session — leaving every front-end route
    // here open while appearing protected on the route list.
    expect($routes)->not->toContain("->middleware('csrf')");

    // The provider legs and the verification link are not CSRF-checked. They
    // arrive from a third party or a mail client and cannot carry a token —
    // HybridAuth's `state` guards the first, and an unguessable one-shot token
    // the second.
    foreach (['callback', 'login', 'link', 'credentials_verify'] as $action) {
        expect($routes)->toContain("Config::routePattern('{$action}')");
    }

    // The callback accepts POST as well as GET, because Apple and OpenID 2.0
    // answer with response_mode=form_post. A GET-only callback 404s them.
    expect($routes)->toMatch("/Route::match\(\['get', 'post'\], Config::routePattern\('callback'\)/");

    // …and that POST must not be CSRF-checked, or every Apple sign-in fails.
    $callbackLine = strstr($routes, "Config::routePattern('callback')");
    $callbackLine = substr($callbackLine, 0, strpos($callbackLine, ';'));

    expect($callbackLine)->not->toContain('csrf');
});

it('logs through the package logger rather than to the screen', function () {
    foreach (packageSources() as $path => $source) {
        expect($source)->not->toMatch('/(?<![\w:>$])(var_dump|print_r|echo)\s*\(/');
    }
});

it('guards every optional framework call it makes at boot', function () {
    // The manager login page renders before much of the CMS is available, and a
    // fatal there locks an administrator out of the only place they could turn
    // the extension off.
    $plugin = file_get_contents(__DIR__ . '/../../plugins/aSocialAuthPlugin.php');

    expect(substr_count($plugin, 'try {'))->toBeGreaterThanOrEqual(substr_count($plugin, "Event::listen("));
});

it('ships a language entry for every message it can show', function () {
    $lines   = require __DIR__ . '/../../lang/en/login.php';
    $missing = [];

    foreach (packageSources() as $source) {
        preg_match_all("/aSocialAuth::login\.([a-z0-9_]+)/", $source, $matches);

        foreach ($matches[1] as $key) {
            if (!array_key_exists($key, $lines)) {
                $missing[$key] = true;
            }
        }
    }

    foreach (packageSources('views') as $source) {
        preg_match_all("/aSocialAuth::login\.([a-z0-9_]+)/", $source, $matches);

        foreach ($matches[1] as $key) {
            if (!array_key_exists($key, $lines)) {
                $missing[$key] = true;
            }
        }
    }

    expect(array_keys($missing))->toBe([]);
});

it('keeps the views free of raw echoes of provider data', function () {
    // Provider profiles are third-party strings; they belong in {{ }}, never in
    // {!! !!}. The only unescaped output is the icon set, which is ours.
    foreach (packageSources('views') as $path => $source) {
        if (basename($path) === 'icon.blade.php') {
            continue;
        }

        expect($source)->not->toContain('{!!', 'unescaped output in ' . basename($path));
    }
});
