<?php

namespace Devrabiul\CookieConsent;

use Exception;
use Illuminate\Support\ServiceProvider;

class CookieConsentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * This method is called after all other service providers have been registered.
     * It is used to perform any actions required to bootstrap the application services.
     *
     * @return void
     * @throws Exception If there is an error during bootstrapping.
     */
    public function boot(): void
    {
        $this->updateProcessingDirectoryConfig();
        $this->app->register(AssetsServiceProvider::class);

        $this->registerResources();
        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }
    }

    /**
     * Register the publishing of configuration files.
     *
     * This method registers the configuration file for publishing to the application's config directory.
     *
     * @return void
     * @throws Exception If there is an error during publishing.
     */
    private function registerPublishing(): void
    {
        // Normal publish
        $this->publishes([
            __DIR__ . '/config/laravel-cookie-consent.php' => config_path('laravel-cookie-consent.php'),
        ]);
    }

    private function registerResources(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/laravel-cookie-consent.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-cookie-consent');
        // $this->commands($this->registerCommands());
    }

    /**
     * Register the application services.
     *
     * This method is called to bind services into the service container.
     * It is used to register the CookieConsent service and load the configuration.
     *
     * @return void
     * @throws Exception If the configuration file cannot be loaded.
     */
    public function register(): void
    {

        $configPath = config_path('laravel-cookie-consent.php');

        if (!file_exists($configPath)) {
            config(['laravel-cookie-consent' => require __DIR__ . '/config/laravel-cookie-consent.php']);
        }

        $this->app->singleton('CookieConsent', function ($app) {
            return new CookieConsent($app['session'], $app['config']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * This method returns an array of services that this provider offers.
     *
     * @return array
     * @throws Exception If there is an error retrieving the services.
     */
    public function provides(): array
    {
        return ['CookieConsent'];
    }

    /**
     * Determine and set the 'system_processing_directory' configuration value.
     *
     * This detects if the current PHP script is being executed from the public directory
     * or the project root directory, or neither, and sets a config value accordingly:
     *
     * - 'public' if script path equals public_path()
     * - 'root' if script path equals base_path()
     * - 'unknown' otherwise
     *
     * This config can be used internally to adapt asset loading or paths.
     *
     * @return void
     */
    private function updateProcessingDirectoryConfig(): void
    {
        // Resolved directly on each boot instead of via persistent cache. The value is a
        // few cheap realpath() comparisons and is inherently runtime-specific — it depends
        // on $_SERVER['SCRIPT_FILENAME'], which differs across HTTP, CLI, queue workers,
        // the scheduler and Octane. Caching it provided no real benefit while causing:
        // cache I/O on every request, unbounded cache-key growth, crashes when the cache
        // backend is unavailable, and QueryExceptions during `package:discover` on fresh
        // installs using CACHE_STORE=database before migrations run. See issues #11 and #14.
        $script = $_SERVER['SCRIPT_FILENAME'] ?? getcwd() ?? '';

        config(['laravel-cookie-consent.system_processing_directory' => $this->resolveProcessingDirectory($script)]);
    }

    /**
     * Resolve where the current PHP script is being executed from.
     *
     * @param string $script The executing script path.
     * @return string One of 'public', 'root', or 'unknown'.
     */
    private function resolveProcessingDirectory(string $script): string
    {
        $scriptPath = realpath(dirname($script));
        $basePath   = realpath(base_path());
        $publicPath = realpath(public_path());

        if ($scriptPath === $publicPath) {
            return 'public';
        } elseif ($scriptPath === $basePath) {
            return 'root';
        }
        return 'unknown';
    }
}
