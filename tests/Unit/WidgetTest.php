<?php

use EvolutionCMS\aSocialAuth\Support\Config;
use EvolutionCMS\aSocialAuth\Support\Renderer;

/**
 * The widget contract: stylable from CSS, embeddable inline or as a modal, and
 * overridable view by view. A sign-in box a site cannot restyle is a sign-in box
 * a site will replace.
 */

beforeEach(fn () => Renderer::resetStyles());

describe('presentation', function () {
    it('renders inline by default', function () {
        expect(Config::widgetMode())->toBe('inline');
    });

    it('can be switched to a modal site-wide', function () {
        TestConfig::set('cms.settings.aSocialAuth.ui.mode', 'modal');

        expect(Config::widgetMode())->toBe('modal');
    });

    it('normalises anything it does not recognise to inline', function () {
        // A snippet parameter is a string typed by a template author; an
        // unrecognised one must degrade to the embeddable form, never to nothing.
        expect(Config::normaliseMode('MODAL'))->toBe('modal');
        expect(Config::normaliseMode('popup'))->toBe('inline');
        expect(Config::normaliseMode(''))->toBe('inline');
        expect(Config::normaliseMode(null))->toBe('inline');
    });
});

describe('styling', function () {
    it('emits its stylesheet once per request however many widgets are shown', function () {
        // Two widgets on one page — sign-in and linked accounts — must not repeat
        // several kilobytes of CSS.
        expect(Renderer::shouldEmitStyles())->toBeTrue();
        expect(Renderer::shouldEmitStyles())->toBeFalse();
        expect(Renderer::shouldEmitStyles())->toBeFalse();
    });

    it('emits nothing at all when the site styles the classes itself', function () {
        TestConfig::set('cms.settings.aSocialAuth.ui.inline_styles', false);

        expect(Renderer::shouldEmitStyles())->toBeFalse();
    });

    it('accepts an extra wrapper class so a theme can scope its own rules', function () {
        TestConfig::set('cms.settings.aSocialAuth.ui.wrapper_class', 'card card--auth');

        expect(Config::wrapperClass())->toBe('card card--auth');
    });

    it('exposes every element through a stable prefixed class', function () {
        // The class names are the package's public styling surface; a site's
        // stylesheet is written against them, so renaming one is a breaking
        // change and belongs in a test.
        $stylesheet = file_get_contents(__DIR__ . '/../../views/partials/styles.blade.php');

        foreach ([
            '.asocialauth',
            '.asocialauth__btn',
            '.asocialauth__form',
            '.asocialauth__input',
            '.asocialauth__submit',
            '.asocialauth__divider',
            '.asocialauth__msg',
            '.asocialauth__list',
            '.asocialauth__item',
            '.asocialauth__trigger',
            '.asocialauth__panel--modal',
        ] as $class) {
            expect($stylesheet)->toContain($class);
        }
    });

    it('drives its colours from custom properties so a retint needs no overrides', function () {
        $stylesheet = file_get_contents(__DIR__ . '/../../views/partials/styles.blade.php');

        foreach (['--asa-bg', '--asa-fg', '--asa-border', '--asa-accent', '--asa-radius'] as $property) {
            expect($stylesheet)->toContain($property);
        }
    });

    it('gives each provider button its own modifier class', function () {
        $view = file_get_contents(__DIR__ . '/../../views/partials/buttons.blade.php');

        expect($view)->toContain('asocialauth__btn--{{ $provider->slug }}');
    });

    it('renders every button list from the one partial', function () {
        // Duplicating the markup is how the manager login page ended up without
        // the per-provider class the front-end widget had. One source, so an
        // icon or a class added for one surface appears on the other.
        foreach (['manager/login-buttons', 'widget/login'] as $view) {
            $source = file_get_contents(__DIR__ . '/../../views/' . $view . '.blade.php');

            expect($source)->toContain("@include('aSocialAuth::partials.buttons'");
            expect($source)->not->toContain('class="asocialauth__btn"');
        }
    });

    it('keeps the modal usable without JavaScript', function () {
        // An un-enhanced <dialog> is display:none, so a trigger with no script
        // behind it would be a dead button. The stylesheet shows it inline
        // instead, and only the script adds `is-enhanced`.
        $stylesheet = file_get_contents(__DIR__ . '/../../views/partials/styles.blade.php');

        expect($stylesheet)->toContain(':not(.is-enhanced)');
    });
});

describe('view overrides', function () {
    it('resolves the packaged views by default', function () {
        expect(Config::view('login'))->toBe('aSocialAuth::widget.login');
        expect(Config::view('identities'))->toBe('aSocialAuth::widget.identities');
        expect(Config::view('manager_login'))->toBe('aSocialAuth::manager.login-buttons');
    });

    it('lets a site point a surface at its own view', function () {
        TestConfig::set('cms.settings.aSocialAuth.ui.views.login', 'theme::auth.box');

        expect(Config::view('login'))->toBe('theme::auth.box');
    });

    it('falls back to the packaged view when the override is blank', function () {
        TestConfig::set('cms.settings.aSocialAuth.ui.views.login', '   ');

        expect(Config::view('login'))->toBe('aSocialAuth::widget.login');
    });
});

describe('which form is shown', function () {
    it('shows the sign-in form by default', function () {
        expect(Renderer::resolveForm(null))->toBe('login');
    });

    it('honours an explicit request', function () {
        expect(Renderer::resolveForm('register'))->toBe('register');
        expect(Renderer::resolveForm('recover'))->toBe('recover');
    });

    it('ignores a form name it does not know', function () {
        expect(Renderer::resolveForm('delete-everything'))->toBe('login');
    });

    it('reads the form from the query string when the snippet did not say', function () {
        $_GET['form'] = 'register';

        expect(Renderer::resolveForm(null))->toBe('register');

        unset($_GET['form']);
    });

    it('lets a reset link override whatever was asked for', function () {
        // Arriving from a reset mail must show the "choose a new password" form
        // whatever the snippet's default was.
        $_GET['hash'] = 'abcdef123456';

        expect(Renderer::resolveForm('login'))->toBe('reset');
        expect(Renderer::resetTokenFromRequest())->toBe('abcdef123456');

        unset($_GET['hash']);
    });

    it('refuses to echo a token-shaped parameter that is not one', function () {
        // The token is rendered into a hidden field, so an arbitrary query value
        // has no business reaching it.
        $_GET['hash'] = '<script>alert(1)</script>';
        expect(Renderer::resetTokenFromRequest())->toBeNull();

        $_GET['hash'] = 'short';
        expect(Renderer::resetTokenFromRequest())->toBeNull();

        $_GET['hash'] = str_repeat('a', 300);
        expect(Renderer::resetTokenFromRequest())->toBeNull();

        unset($_GET['hash']);
    });
});

describe('snippet parameters', function () {
    it('reads a string "0" as off, the way a snippet tag supplies it', function () {
        $flag = new ReflectionMethod(Renderer::class, 'flag');
        $flag->setAccessible(true);

        expect($flag->invoke(null, '0'))->toBeFalse();
        expect($flag->invoke(null, 'false'))->toBeFalse();
        expect($flag->invoke(null, 'no'))->toBeFalse();
        expect($flag->invoke(null, ''))->toBeFalse();

        expect($flag->invoke(null, '1'))->toBeTrue();
        expect($flag->invoke(null, 'true'))->toBeTrue();
        expect($flag->invoke(null, true))->toBeTrue();
    });
});

it('renders nothing when the package is switched off', function () {
    TestConfig::set('cms.settings.aSocialAuth.enable', false);

    expect(Renderer::loginWidget())->toBe('');
    expect(Renderer::loginButtons())->toBe('');
    expect(Renderer::identitiesWidget())->toBe('');
});

it('renders nothing rather than an empty box when there is nothing to offer', function () {
    TestConfig::set('cms.settings.aSocialAuth.enable', true);

    // No providers are configured with credentials in a unit test, and the site
    // turned the credential forms off.
    expect(Renderer::loginWidget(null, ['credentials' => false]))->toBe('');
});
