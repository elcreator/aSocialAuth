<?php

use Elcreator\aSocialAuth\Support\Config;

/**
 * Redirect handling, and the open-redirect defence around the return parameter.
 */

it('resolves the natural root for each context', function () {
    expect(Config::absoluteFor(Config::CONTEXT_WEB, ''))->toBe('https://example.test/');
    expect(Config::absoluteFor(Config::CONTEXT_MGR, ''))->toBe('https://example.test/manager/');
});

it('resolves a relative path against the right root', function () {
    expect(Config::absoluteFor(Config::CONTEXT_WEB, '/members'))
        ->toBe('https://example.test/members');

    expect(Config::absoluteFor(Config::CONTEXT_MGR, 'index.php?a=2'))
        ->toBe('https://example.test/manager/index.php?a=2');
});

it('passes an absolute configured URL through untouched', function () {
    expect(Config::absoluteFor(Config::CONTEXT_WEB, 'https://elsewhere.test/landing'))
        ->toBe('https://elsewhere.test/landing');
});

it('sends a web login to the configured web destination', function () {
    TestConfig::set('cms.settings.aSocialAuth.redirects.web', '/members/dashboard');

    expect(Config::loginRedirect(Config::CONTEXT_WEB))
        ->toBe('https://example.test/members/dashboard');
});

describe('return path sanitising', function () {
    it('accepts a same-site absolute path', function () {
        expect(Config::sanitizeReturnPath('/members/profile'))->toBe('/members/profile');
        expect(Config::sanitizeReturnPath('/a?b=c&d=e'))->toBe('/a?b=c&d=e');
    });

    it('rejects anything that could leave the site', function () {
        // Each of these is a way of smuggling an off-site destination past a
        // naive "starts with a slash" check.
        expect(Config::sanitizeReturnPath('https://evil.test/'))->toBeNull();
        expect(Config::sanitizeReturnPath('//evil.test/'))->toBeNull();
        expect(Config::sanitizeReturnPath('/\\evil.test/'))->toBeNull();
        expect(Config::sanitizeReturnPath('\\\\evil.test'))->toBeNull();
        expect(Config::sanitizeReturnPath('javascript:alert(1)'))->toBeNull();
        expect(Config::sanitizeReturnPath('members/profile'))->toBeNull();
    });

    it('rejects control characters used to split headers', function () {
        expect(Config::sanitizeReturnPath("/ok\r\nLocation: https://evil.test"))->toBeNull();
        expect(Config::sanitizeReturnPath("/ok\0"))->toBeNull();
    });

    it('rejects non-strings and empties', function () {
        expect(Config::sanitizeReturnPath(null))->toBeNull();
        expect(Config::sanitizeReturnPath(''))->toBeNull();
        expect(Config::sanitizeReturnPath(['/a']))->toBeNull();
    });
});

describe('context resolution', function () {
    it('uses the configured context verbatim when it is not auto', function () {
        TestConfig::set('cms.settings.aSocialAuth.context', 'web');
        expect(Config::resolveContext('https://example.test/manager/index.php'))->toBe('web');

        TestConfig::set('cms.settings.aSocialAuth.context', 'mgr');
        expect(Config::resolveContext('https://example.test/'))->toBe('mgr');
    });

    it('falls back to mgr when the configured context is nonsense', function () {
        TestConfig::set('cms.settings.aSocialAuth.context', 'sideways');
        expect(Config::configuredContext())->toBe('mgr');
    });

    it('picks the context from where the flow started when set to auto', function () {
        TestConfig::set('cms.settings.aSocialAuth.context', 'auto');

        expect(Config::resolveContext('https://example.test/manager/index.php'))->toBe('mgr');
        expect(Config::resolveContext('https://example.test/members/login'))->toBe('web');
        expect(Config::resolveContext(''))->toBe('web');
    });

    it('does not mistake a lookalike path for the manager', function () {
        TestConfig::set('cms.settings.aSocialAuth.context', 'auto');

        expect(Config::resolveContext('https://example.test/manager-tips/'))->toBe('web');
        expect(Config::resolveContext('https://example.test/manager'))->toBe('mgr');
        expect(Config::resolveContext('https://example.test/manager/'))->toBe('mgr');
    });
});
