<?php

use Elcreator\aSocialAuth\Console\PackagesCommand;
use Elcreator\aSocialAuth\Support\OneTap;
use Elcreator\aSocialAuth\Support\Packages;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Packages a feature needs are optional: without them the feature is absent,
 * and everything else works.
 */

/** @return array{0: int, 1: string, 2: string[]} exit code, output, packages installed */
function runPackagesCommand(array $install = [], int $result = 0): array
{
    $command = new class extends PackagesCommand {
        public array $installed = [];
        public int $result = 0;

        protected function installPackage(string $package): int
        {
            $this->installed[] = $package;

            return $this->result;
        }
    };
    $command->result = $result;

    $input  = new ArrayInput(['--install' => $install], $command->getDefinition());
    $buffer = new BufferedOutput();
    $command->setInput($input);
    $command->setOutput(new OutputStyle($input, $buffer));

    return [$command->handle(), $buffer->fetch(), $command->installed];
}

afterEach(function () {
    Packages::$fake = [];
});

it('suggests the packages instead of requiring them', function () {
    $composer = json_decode(file_get_contents(__DIR__ . '/../../composer.json'), true);

    foreach (Packages::features() as $feature) {
        foreach (Packages::packages($feature) as $package) {
            expect($composer['require'])->not->toHaveKey($package);
            expect($composer['suggest'])->toHaveKey($package);
            expect($composer['conflict'])->toHaveKey($package);
            // The suite itself runs with them.
            expect($composer['require-dev'])->toHaveKey($package);
        }
    }
});

it('finds an installed package', function () {
    expect(Packages::available(Packages::ONE_TAP))->toBeTrue()
        ->and(Packages::missing(Packages::ONE_TAP))->toBe([])
        ->and(Packages::available('nonsense'))->toBeFalse();
});

it('lists every feature with the command that installs it', function () {
    Packages::$fake = [Packages::ONE_TAP => false];

    [$code, $output] = runPackagesCommand();

    expect($code)->toBe(0)
        ->and($output)->toContain('onetap')
        ->and($output)->toContain('missing')
        ->and($output)->toContain('asocialauth:packages --install=onetap');
});

it('does not reinstall what is there', function () {
    [$code, $output, $installed] = runPackagesCommand(['onetap']);

    expect($code)->toBe(0)
        ->and($installed)->toBe([])
        ->and($output)->toContain('already installed');
});

it('refuses a feature it does not know, so nothing else can be installed through it', function () {
    [$code, , $installed] = runPackagesCommand(['onetap,evil/package']);

    expect($code)->toBe(1)->and($installed)->toBe([]);
});

it('keeps One Tap absent while its package is missing', function () {
    TestConfig::set('cms.settings.aSocialAuth.enable', true);
    TestConfig::set('cms.settings.aSocialAuth.providers.google.enabled', true);
    TestConfig::set('cms.settings.aSocialAuth.providers.google.keys', ['id' => 'id', 'secret' => 's']);
    TestConfig::set('cms.settings.aSocialAuth.providers.google.one_tap', ['enabled' => true]);
    $_SESSION['_token'] = 'csrf';
    OneTap::reset();

    Packages::$fake = [Packages::ONE_TAP => false];

    expect(OneTap::viewData())->toBeNull()
        ->and(OneTap::provider())->toBeNull();

    $request = \Illuminate\Http\Request::create('/asocialauth/google/onetap', 'POST', ['credential' => 'x']);

    expect(fn () => (new \Elcreator\aSocialAuth\Http\Controllers\OneTapController())($request, 'google'))
        ->toThrow(HttpException::class);

    // …and the ordinary button is still offered.
    expect(\Elcreator\aSocialAuth\Support\ProviderRegistry::resolve('google'))->not->toBeNull();
});

it('never loads the package where the feature is not used', function () {
    foreach (glob(__DIR__ . '/../../src/{,*/,*/*/}*.php', GLOB_BRACE) as $file) {
        if (in_array(basename($file), ['IdTokenVerifier.php', 'Packages.php'], true)) {
            continue;
        }

        expect(file_get_contents($file))->not->toContain('Firebase\\', basename($file));
    }
});

it('is inert when the site runs without visitor sessions', function () {
    // Nothing here can work without one: the CSRF token, the OAuth state and the
    // login itself all live in it. A constant, hence a process of its own.
    $script = <<<'PHP'
        define('NO_SESSION', %s);
        function config($key, $default = null) { return true; }
        require %s;
        echo json_encode(Elcreator\aSocialAuth\Support\Config::isEnabled());
        PHP;
    $run = static fn (string $value): string => (string) shell_exec(
        escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg(str_replace(
            "\n",
            ' ',
            sprintf($script, $value, "'" . str_replace('\\', '/', realpath(__DIR__ . '/../../src/Support/Config.php')) . "'")
        ))
    );

    expect($run('true'))->toBe('false')
        ->and($run('false'))->toBe('true');
});
