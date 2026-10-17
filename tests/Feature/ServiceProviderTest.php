<?php

use Devrabiul\CookieConsent\CookieConsent as CookieConsentService;
use Devrabiul\CookieConsent\CookieConsentServiceProvider;
use Devrabiul\CookieConsent\Facades\CookieConsent;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

it('registers the CookieConsent singleton', function () {
    $instance = app('CookieConsent');

    expect($instance)->toBeInstanceOf(CookieConsentService::class)
        ->and(app('CookieConsent'))->toBe($instance);
});

it('resolves the facade to the singleton', function () {
    expect(CookieConsent::getFacadeRoot())->toBe(app('CookieConsent'));
});

it('declares the services it provides', function () {
    expect((new CookieConsentServiceProvider(app()))->provides())->toBe(['CookieConsent']);
});

it('registers the package views', function () {
    expect(view()->exists('laravel-cookie-consent::cookie-consent'))->toBeTrue();
});

it('registers the config file for publishing', function () {
    $paths = ServiceProvider::pathsToPublish(CookieConsentServiceProvider::class);

    expect(array_values($paths))->toContain(config_path('laravel-cookie-consent.php'));
});

it('registers the @cookieConsent Blade directives', function () {
    $directives = array_keys(Blade::getCustomDirectives());

    expect($directives)->toContain('cookieConsent', 'elsecookieConsent', 'endcookieConsent')
        ->and(Blade::check('cookieConsent', 'analytics'))->toBeFalse();
});

it('no longer registers the script-utils route', function () {
    expect(Route::has('laravel-cookie-consent.script-utils'))->toBeFalse();

    $this->get('/laravel-cookie-consent/script-utils')->assertNotFound();
});

it('resolves the system processing directory on boot', function () {
    expect(config('laravel-cookie-consent.system_processing_directory'))
        ->toBeIn(['public', 'root', 'unknown']);
});
