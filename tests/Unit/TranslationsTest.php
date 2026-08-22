<?php

/**
 * Every manager language Evolution CMS ships with should find its own strings
 * here. A locale that is merely missing renders as its own key on a site whose
 * translator has no fallback, so the set is checked rather than assumed.
 */

function langRoot(): string
{
    return dirname(__DIR__, 2) . '/lang';
}

function langFile(string $locale, string $file): array
{
    return require langRoot() . '/' . $locale . '/' . $file . '.php';
}

function shippedLocales(): array
{
    $locales = array_map('basename', glob(langRoot() . '/*', GLOB_ONLYDIR));

    sort($locales);

    return $locales;
}

it('ships a translation for every manager language Evolution CMS offers', function () {
    // core/lang/<dir>, by the locale each one sets as modx_lang_attribute --
    // note 'nn' declares itself 'no', so both names are carried.
    $expected = [
        'az', 'be', 'bg', 'cs', 'da', 'de', 'en', 'es', 'fa', 'fi', 'fr',
        'he', 'it', 'ja', 'nl', 'nn', 'no', 'pl', 'pt', 'sv', 'uk', 'zh',
    ];

    expect(shippedLocales())->toBe($expected);
});

it('gives every locale the same keys English has', function () {
    $reference = array_keys(langFile('en', 'login'));
    $global    = array_keys(langFile('en', 'global'));

    foreach (shippedLocales() as $locale) {
        expect(array_keys(langFile($locale, 'login')))
            ->toBe($reference, "lang/{$locale}/login.php has drifted from English");

        expect(array_keys(langFile($locale, 'global')))
            ->toBe($global, "lang/{$locale}/global.php has drifted from English");
    }
});

it('keeps every placeholder a line was written with', function () {
    $reference = langFile('en', 'login');
    $dropped   = [];

    foreach (shippedLocales() as $locale) {
        $lines = langFile($locale, 'login');

        foreach ($reference as $key => $english) {
            preg_match_all('/:[a-z_]+/', $english, $wanted);

            foreach ($wanted[0] as $placeholder) {
                if (!str_contains($lines[$key], $placeholder)) {
                    $dropped[] = "lang/{$locale}/login.php: {$key} dropped {$placeholder}";
                }
            }
        }
    }

    expect($dropped)->toBe([]);
});
