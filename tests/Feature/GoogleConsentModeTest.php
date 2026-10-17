<?php

use Devrabiul\CookieConsent\CookieConsent as CookieConsentService;
use Devrabiul\CookieConsent\Facades\CookieConsent;
use Illuminate\Http\Request;

function googleSnippetSettings(string $html): array
{
    preg_match('/window\.cookieConsentGoogleConsentMode = (\{.*?\});\n/s', $html, $matches);

    return json_decode($matches[1] ?? 'null', true) ?? [];
}

beforeEach(function () {
    config([
        'laravel-cookie-consent.cookie_prefix' => 'Laravel_App',
        'laravel-cookie-consent.cookie_categories.analytics.enabled' => true,
        'laravel-cookie-consent.cookie_categories.marketing.enabled' => true,
        'laravel-cookie-consent.google_consent_mode.enabled' => true,
    ]);
});

it('is disabled by default', function () {
    $config = require __DIR__ . '/../../src/config/laravel-cookie-consent.php';

    expect($config['google_consent_mode']['enabled'])->toBeFalse()
        ->and($config['google_consent_mode']['consent_types'])->toBe(CookieConsentService::DEFAULT_GOOGLE_CONSENT_TYPES);
});

it('outputs nothing when disabled', function () {
    config(['laravel-cookie-consent.google_consent_mode.enabled' => false]);

    expect(CookieConsent::googleConsentMode())->toBe('')
        ->and(CookieConsent::styles())->not->toContain('cookieConsentGoogleConsentMode')
        ->and(CookieConsent::googleConsentTypes())->toBeNull()
        ->and(CookieConsent::googleConsentState())->toBe([]);
});

it('outputs nothing for older published configs without the option', function () {
    $config = config('laravel-cookie-consent');
    unset($config['google_consent_mode']);
    config(['laravel-cookie-consent' => $config]);

    expect(CookieConsent::googleConsentMode())->toBe('');
});

it('outputs nothing when the banner is disabled', function () {
    config(['laravel-cookie-consent.enabled' => false]);

    expect(CookieConsent::googleConsentMode())->toBe('');
});

it('is included in styles() for the <head>', function () {
    $html = CookieConsent::styles();

    expect($html)
        ->toContain('<link rel="stylesheet"')
        ->toContain("window.gtag('consent', 'default', defaults);")
        ->toEndWith('</script>');
});

it('is output at most once per request', function () {
    expect(CookieConsent::googleConsentMode())->toContain('cookieConsentGoogleConsentMode')
        ->and(CookieConsent::styles())->not->toContain('cookieConsentGoogleConsentMode')
        ->and(CookieConsent::googleConsentMode())->toBe('');
});

it('passes the consent settings to the head snippet', function () {
    config([
        'laravel-cookie-consent.consent_version' => '3.0',
        'laravel-cookie-consent.cookie_lifetime' => 30,
        'laravel-cookie-consent.google_consent_mode.wait_for_update' => 1000,
        'laravel-cookie-consent.google_consent_mode.url_passthrough' => true,
    ]);

    expect(googleSnippetSettings(CookieConsent::googleConsentMode()))->toBe([
        'cookieName' => 'laravel-app_consent_preferences',
        'version' => '3.0',
        'cookieLifetime' => 30,
        'rejectLifetime' => 7,
        'maxCookieLength' => 4096,
        'clockSkewTolerance' => 86400,
        'types' => CookieConsentService::DEFAULT_GOOGLE_CONSENT_TYPES,
        'enabledCategories' => ['necessary', 'analytics', 'marketing'],
        'grantedCategories' => ['necessary'],
        'waitForUpdate' => 1000,
        'adsDataRedaction' => true,
        'urlPassthrough' => true,
    ]);
});

it('uses a custom consent type mapping and ignores unknown types', function () {
    config(['laravel-cookie-consent.google_consent_mode.consent_types' => [
        'analytics_storage' => 'statistics',
        'ad_storage' => 'marketing',
        'made_up_storage' => 'marketing',
        'ad_user_data' => '',
        'ad_personalization' => ['marketing'],
    ]]);

    expect(CookieConsent::googleConsentTypes())->toBe([
        'analytics_storage' => 'statistics',
        'ad_storage' => 'marketing',
    ]);
});

it('falls back to the default mapping when consent_types is missing', function () {
    config(['laravel-cookie-consent.google_consent_mode.consent_types' => null]);

    expect(CookieConsent::googleConsentTypes())->toBe(CookieConsentService::DEFAULT_GOOGLE_CONSENT_TYPES);
});

it('escapes configured values inside the inline script', function () {
    config(['laravel-cookie-consent.consent_version' => '</script><script>alert(1)</script>']);

    expect(CookieConsent::googleConsentMode())
        ->not->toContain('</script><script>alert(1)')
        ->toContain('</script>');
});

it('passes the consent type mapping to the banner script', function () {
    $html = CookieConsent::scripts()->render();

    expect($html)->toContain('data-google-consent-mode="' . e(json_encode(CookieConsentService::DEFAULT_GOOGLE_CONSENT_TYPES)) . '"');

    config(['laravel-cookie-consent.google_consent_mode.enabled' => false]);
    expect(CookieConsent::scripts()->render())->not->toContain('data-google-consent-mode');
});

it('reports the Google consent state for the visitor', function () {
    $request = Request::create('/', 'GET', [], [
        'laravel-app_consent_preferences' => json_encode([
            'version' => '1.0',
            'status' => 'accepted',
            'timestamp' => time() - 60,
            'categories' => ['necessary' => true, 'analytics' => true, 'marketing' => false],
        ]),
    ]);

    expect(CookieConsent::googleConsentState($request))->toBe([
        'ad_storage' => 'denied',
        'ad_user_data' => 'denied',
        'ad_personalization' => 'denied',
        'analytics_storage' => 'granted',
        'functionality_storage' => 'denied',
        'personalization_storage' => 'denied',
        'security_storage' => 'granted',
    ]);
});

it('grants only locked categories without consent', function () {
    expect(CookieConsent::googleConsentState(Request::create('/')))->toBe([
        'ad_storage' => 'denied',
        'ad_user_data' => 'denied',
        'ad_personalization' => 'denied',
        'analytics_storage' => 'denied',
        'functionality_storage' => 'denied',
        'personalization_storage' => 'denied',
        'security_storage' => 'granted',
    ]);
});
