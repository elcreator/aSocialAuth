<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

/**
 * Every Blade template must compile to valid PHP.
 *
 * Worth its own test because a Blade error surfaces at render time, and the
 * place these render is a login page — the one screen where a fatal locks an
 * administrator out of the only place they could switch the extension off.
 */

function compileView(string $relativePath): string
{
    $cache = sys_get_temp_dir() . '/asocialauth-blade-' . getmypid();

    if (!is_dir($cache)) {
        mkdir($cache, 0775, true);
    }

    $compiler = new BladeCompiler(new Filesystem(), $cache);

    return $compiler->compileString(file_get_contents(__DIR__ . '/../../views/' . $relativePath));
}

dataset('views', [
    'manager/login-buttons.blade.php',
    'widget/login.blade.php',
    'widget/identities.blade.php',
    'partials/styles.blade.php',
    'partials/icon.blade.php',
    'partials/buttons.blade.php',
    'partials/credentials.blade.php',
]);

it('compiles to syntactically valid PHP', function (string $view) {
    $compiled = compileView($view);

    expect($compiled)->not->toBe('');

    // php -l needs a file; compiling to a string and linting it is the only way
    // to catch a malformed directive without rendering.
    $tmp = tempnam(sys_get_temp_dir(), 'asa') . '.php';
    file_put_contents($tmp, $compiled);

    $output = [];
    $status = 0;
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($tmp) . ' 2>&1', $output, $status);

    @unlink($tmp);

    expect($status)->toBe(0, "Blade output for {$view} is not valid PHP:\n" . implode("\n", $output));
})->with('views');

it('references only partials that exist', function (string $view) {
    $source = file_get_contents(__DIR__ . '/../../views/' . $view);

    preg_match_all("/@include\(\s*'aSocialAuth::([a-z0-9_.\-]+)'/i", $source, $matches);

    $missing = [];

    foreach ($matches[1] as $included) {
        $path = __DIR__ . '/../../views/' . str_replace('.', '/', $included) . '.blade.php';

        if (!is_file($path)) {
            $missing[] = $included;
        }
    }

    expect($missing)->toBe([], "{$view} includes partials that do not exist");
})->with('views');
