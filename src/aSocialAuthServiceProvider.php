<?php

namespace Elcreator\aSocialAuth;

use Elcreator\aSocialAuth\Support\Config;
use Elcreator\aSocialAuth\Support\Log;
use Elcreator\aSocialAuth\Support\Renderer;
use EvolutionCMS\ServiceProvider;

class aSocialAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadPluginsFrom(dirname(__DIR__) . '/plugins/');
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/aSocialAuth.php', 'cms.settings.aSocialAuth');

        $this->loadMigrationsFrom(dirname(__DIR__) . '/database/migrations');
        $this->loadTranslationsFrom(dirname(__DIR__) . '/lang', 'aSocialAuth');
        $this->ensureTranslationFallback();
        $this->loadViewsFrom(dirname(__DIR__) . '/views', 'aSocialAuth');

        $this->registerSnippets();
        $this->loadRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishResources();
        }
    }

    /**
     * Give the translator a fallback locale when the CMS has not.
     *
     * Evolution's container answers null to getFallbackLocale(), so Laravel's
     * translator boots without one: under a manager language we ship no file
     * for, every line would render as its own key ("aSocialAuth::login.sign_in")
     * instead of falling back to English. Only filled in when nothing else has
     * set it, and a fallback can only supply lines the active locale lacks.
     */
    protected function ensureTranslationFallback(): void
    {
        try {
            $translator = $this->app['translator'];
        } catch (\Throwable $e) {
            return;
        }

        if (!method_exists($translator, 'getFallback') || !method_exists($translator, 'setFallback')) {
            return;
        }

        if ((string) $translator->getFallback() !== '') {
            return;
        }

        $fallback = trim((string) config('app.fallback_locale', 'en'));

        $translator->setFallback($fallback !== '' ? $fallback : 'en');
    }

    /**
     * Routes are only registered when the package is switched on.
     *
     * Leaving them registered while disabled would answer with a redirect or a
     * 500 instead of a 404, which tells anyone probing that the extension is
     * installed. Off means absent.
     */
    protected function loadRoutes(): void
    {
        if (!Config::isEnabled()) {
            return;
        }

        include __DIR__ . '/Http/routes.php';
    }

    /**
     * Front-end snippets, registered at boot so the parser finds them.
     *
     * `[[aSocialAuthButtons]]`     – sign-in buttons for a front-end login page
     * `[[aSocialAuthIdentities]]`  – linked-accounts widget for a profile page
     *
     * Both accept `&return=` to come back to the page they were placed on.
     */
    protected function registerSnippets(): void
    {
        // The full sign-in widget: credential forms plus provider buttons.
        //
        //   [[aSocialAuthLogin]]
        //   [[aSocialAuthLogin? &mode=`modal` &trigger=`Sign in`]]
        //   [[aSocialAuthLogin? &form=`register` &providers=`0`]]
        $widget = function ($params = []) {
            $params = is_array($params) ? $params : [];

            try {
                return Renderer::loginWidget(
                    Config::sanitizeReturnPath($params['return'] ?? null),
                    $params
                );
            } catch (\Throwable $e) {
                Log::warning('aSocialAuthLogin snippet failed: ' . $e->getMessage());

                return '';
            }
        };

        $this->addSnippet('aSocialAuthLogin', $widget);

        // Kept as an alias: installs that already put [[aSocialAuthButtons]] on
        // a template should not have to edit it. Buttons only, no forms.
        $this->addSnippet('aSocialAuthButtons', function ($params = []) use ($widget) {
            $params = is_array($params) ? $params : [];
            $params['credentials'] = false;

            return $widget($params);
        });

        // The credential forms on their own, for a site that lays out its own
        // sign-in page and only wants the form.
        $this->addSnippet('aSocialAuthCredentials', function ($params = []) use ($widget) {
            $params = is_array($params) ? $params : [];
            $params['providers'] = false;

            return $widget($params);
        });

        $this->addSnippet('aSocialAuthIdentities', function ($params = []) {
            try {
                return Renderer::identitiesWidget(
                    Config::sanitizeReturnPath(is_array($params) ? ($params['return'] ?? null) : null)
                );
            } catch (\Throwable $e) {
                Log::warning('aSocialAuthIdentities snippet failed: ' . $e->getMessage());

                return '';
            }
        });
    }

    protected function publishResources(): void
    {
        $this->publishes([
            dirname(__DIR__) . '/config/aSocialAuth.php' => config_path('cms/settings/aSocialAuth.php', true),
        ], 'asocialauth-config');

        $this->publishes([
            dirname(__DIR__) . '/views' => base_path('views/vendor/asocialauth'),
        ], 'asocialauth-views');

        $langRoot   = $this->resolveLangVendorPath('asocialauth');
        $langSource = dirname(__DIR__) . '/lang';

        if (is_dir($langSource)) {
            $langFiles = $this->collectPublishFiles($langSource, $langRoot);

            if ($langFiles !== []) {
                $this->publishes($langFiles, 'asocialauth-lang');
            }
        }
    }

    protected function resolveLangVendorPath(string $package): string
    {
        $base = base_path('lang/vendor');

        if (!is_dir($base)) {
            $base = base_path('resources/lang/vendor');
        }

        return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $package;
    }

    protected function collectPublishFiles(string $sourceDir, string $targetDir): array
    {
        if (!is_dir($sourceDir)) {
            return [];
        }

        $files    = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS)
        );

        $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR);
        $targetDir = rtrim($targetDir, DIRECTORY_SEPARATOR);

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $path     = $file->getPathname();
            $relative = substr($path, strlen($sourceDir) + 1);

            $files[$path] = $targetDir . DIRECTORY_SEPARATOR . $relative;
        }

        return $files;
    }
}
