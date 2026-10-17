<?php

use Devrabiul\CookieConsent\CookieConsent as CookieConsentService;
use Devrabiul\CookieConsent\Facades\CookieConsent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

function consentRecordCookie(array $categories, string $status = 'accepted', string $version = '1.0', ?int $timestamp = null): string
{
    return json_encode([
        'version' => $version,
        'status' => $status,
        'timestamp' => $timestamp ?? time() - 60,
        'categories' => (object)$categories,
    ]);
}

function requestWithConsent(array $cookies): Request
{
    return Request::create('/', 'GET', [], $cookies);
}

beforeEach(function () {
    config([
        'laravel-cookie-consent.cookie_prefix' => 'Laravel_App',
        'laravel-cookie-consent.cookie_categories.analytics.enabled' => true,
        'laravel-cookie-consent.cookie_categories.marketing.enabled' => true,
    ]);
});

it('uses stable cookie names without the calendar year', function () {
    expect(CookieConsent::consentCookieName())->toBe('laravel-app_consent')
        ->and(CookieConsent::preferencesCookieName())->toBe('laravel-app_consent_preferences')
        ->and(CookieConsent::consentCookieName())->not->toContain(date('Y'));
});

it('falls back to a generic cookie name when the prefix has no sluggable characters', function () {
    config(['laravel-cookie-consent.cookie_prefix' => '***']);

    expect(CookieConsent::consentCookieName())->toBe('cookie_consent');
});

it('reads consent from the current request by default', function () {
    $this->app->instance('request', requestWithConsent([
        'laravel-app_consent_preferences' => consentRecordCookie(['necessary' => true, 'analytics' => true, 'marketing' => false]),
    ]));

    expect(CookieConsent::hasConsent('analytics'))->toBeTrue()
        ->and(CookieConsent::hasConsent('marketing'))->toBeFalse()
        ->and(CookieConsent::hasConsent('necessary'))->toBeTrue()
        ->and(CookieConsent::hasValidConsent())->toBeTrue();
});

it('returns false for categories that are not configured', function () {
    $request = requestWithConsent([
        'laravel-app_consent_preferences' => consentRecordCookie(['unknown' => true, 'analytics' => true]),
    ]);

    expect(CookieConsent::hasConsent('unknown', $request))->toBeFalse()
        ->and(CookieConsent::hasConsent('', $request))->toBeFalse();
});

it('returns false for categories disabled in the config', function () {
    config(['laravel-cookie-consent.cookie_categories.analytics.enabled' => false]);

    $request = requestWithConsent([
        'laravel-app_consent_preferences' => consentRecordCookie(['analytics' => true]),
    ]);

    expect(CookieConsent::hasConsent('analytics', $request))->toBeFalse();
});

it('returns false when cookie_categories is not an array', function () {
    config(['laravel-cookie-consent.cookie_categories' => null]);

    $request = requestWithConsent([
        'laravel-app_consent_preferences' => consentRecordCookie(['analytics' => true]),
    ]);

    expect(CookieConsent::hasConsent('analytics', $request))->toBeFalse();
});

it('exposes the validated consent record', function () {
    $timestamp = time() - 3600;
    $request = requestWithConsent([
        'laravel-app_consent_preferences' => consentRecordCookie(['analytics' => true, 'marketing' => 'yes'], 'accepted', '1.0', $timestamp),
    ]);

    expect(CookieConsent::consentRecord($request))->toBe([
        'version' => '1.0',
        'status' => 'accepted',
        'timestamp' => $timestamp,
        'categories' => ['analytics' => true],
    ]);
});

it('returns null for the consent record when there is no consent', function () {
    expect(CookieConsent::consentRecord(requestWithConsent([])))->toBeNull();
});

it('compares a numeric consent_version as a string', function () {
    config(['laravel-cookie-consent.consent_version' => 2.5]);

    $request = requestWithConsent([
        'laravel-app_consent_preferences' => consentRecordCookie(['analytics' => true], 'accepted', '2.5'),
    ]);

    expect(CookieConsent::consentVersion())->toBe('2.5')
        ->and(CookieConsent::hasConsent('analytics', $request))->toBeTrue();
});

it('falls back to the default consent version when it is empty', function ($version) {
    config(['laravel-cookie-consent.consent_version' => $version]);

    expect(CookieConsent::consentVersion())->toBe(CookieConsentService::DEFAULT_CONSENT_VERSION);
})->with([
    'null' => [null],
    'empty string' => [''],
    'whitespace' => ['   '],
    'array' => [['1.0']],
]);

it('treats a missing consent_version (older published configs) as 1.0', function () {
    $config = config('laravel-cookie-consent');
    unset($config['consent_version']);
    config(['laravel-cookie-consent' => $config]);

    $request = requestWithConsent([
        'laravel-app_consent_preferences' => consentRecordCookie(['analytics' => true]),
    ]);

    expect(CookieConsent::hasConsent('analytics', $request))->toBeTrue();
});

it('requests consent again after the version is bumped', function () {
    $request = requestWithConsent([
        'laravel-app_consent_preferences' => consentRecordCookie(['analytics' => true], 'accepted', '1.0'),
    ]);

    expect(CookieConsent::hasConsent('analytics', $request))->toBeTrue();

    config(['laravel-cookie-consent.consent_version' => '1.1']);

    expect(CookieConsent::hasConsent('analytics', $request))->toBeFalse()
        ->and(CookieConsent::hasValidConsent($request))->toBeFalse();
});

it('respects a shortened cookie_lifetime for existing consent', function () {
    $request = requestWithConsent([
        'laravel-app_consent_preferences' => consentRecordCookie(['analytics' => true], 'accepted', '1.0', time() - 10 * 86400),
    ]);

    expect(CookieConsent::hasConsent('analytics', $request))->toBeTrue();

    config(['laravel-cookie-consent.cookie_lifetime' => 5]);

    expect(CookieConsent::hasConsent('analytics', $request))->toBeFalse();
});

it('reads the first matching cookie from the raw header and ignores similar names', function () {
    $value = rawurlencode(consentRecordCookie(['analytics' => true]));
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_COOKIE' => "other_laravel-app_consent_preferences=x; laravel-app_consent_preferences={$value}; broken",
    ]);

    expect(CookieConsent::hasConsent('analytics', $request))->toBeTrue();
});

it('returns false without throwing when no request is bound', function () {
    $this->app->forgetInstance('request');
    $this->app->offsetUnset('request');

    expect(CookieConsent::hasConsent('analytics'))->toBeFalse()
        ->and(CookieConsent::hasValidConsent())->toBeFalse();
});

it('reads consent cookies even when EncryptCookies cannot decrypt them', function () {
    Route::middleware('web')->get('/__consent-check', function (Request $request) {
        return response()->json([
            'cookie_bag' => $request->cookie('laravel-app_consent_preferences'),
            'analytics' => CookieConsent::hasConsent('analytics'),
            'marketing' => CookieConsent::hasConsent('marketing'),
        ]);
    });

    $record = consentRecordCookie(['analytics' => true, 'marketing' => false]);

    // A real browser request carries the cookie both in $_COOKIE and in the Cookie header
    $response = $this->call('GET', '/__consent-check', [], ['laravel-app_consent_preferences' => $record], [], [
        'HTTP_COOKIE' => 'laravel-app_consent_preferences=' . rawurlencode($record),
    ]);

    $response->assertOk()->assertJson([
        'cookie_bag' => null, // nulled by EncryptCookies: the browser wrote it unencrypted
        'analytics' => true,
        'marketing' => false,
    ]);
});
