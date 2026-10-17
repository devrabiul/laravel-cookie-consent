<?php

use Devrabiul\CookieConsent\Facades\CookieConsent;

/**
 * Load the package config file with the given environment variables set.
 */
function loadPackageConfig(array $env = []): array
{
    foreach ($env as $key => $value) {
        $_ENV[$key] = $_SERVER[$key] = $value;
    }

    try {
        return require __DIR__ . '/../../src/config/laravel-cookie-consent.php';
    } finally {
        foreach (array_keys($env) as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }
}

it('ships sensible defaults', function () {
    $config = loadPackageConfig();

    expect($config['enabled'])->toBeTrue()
        ->and($config['consent_version'])->toBe('1.0')
        ->and($config['cookie_lifetime'])->toBe(365)
        ->and($config['reject_lifetime'])->toBe(7)
        ->and($config['cookie_categories']['necessary']['locked'])->toBeTrue()
        ->and($config['cookie_categories']['analytics']['enabled'])->toBeFalse();
});

it('loads the package config when the application has not published it', function () {
    expect(config('laravel-cookie-consent.consent_version'))->toBe('1.0')
        ->and(config('laravel-cookie-consent.cookie_lifetime'))->toBe(365);
});

it('reads overrides from the environment', function () {
    $config = loadPackageConfig([
        'COOKIE_CONSENT_VERSION' => '2026-10',
        'COOKIE_CONSENT_LIFETIME' => '30',
        'COOKIE_REJECT_LIFETIME' => '2',
        'COOKIE_CONSENT_ENABLED' => 'false',
    ]);

    expect($config['consent_version'])->toBe('2026-10')
        ->and($config['cookie_lifetime'])->toBe('30')
        ->and($config['reject_lifetime'])->toBe('2')
        ->and($config['enabled'])->toBeFalse();
});

it('falls back to relative policy paths when no URL is configured', function (array $env) {
    $links = loadPackageConfig($env)['policy_links'];

    expect($links[0]['link'])->toBe('privacy-policy')
        ->and($links[1]['link'])->toBe('terms-and-conditions');
})->with([
    'not set' => [[]],
    'empty strings' => [['COOKIE_CONSENT_PRIVACY_POLICY_URL' => '', 'COOKIE_CONSENT_TERMS_URL' => '']],
]);

it('keeps explicitly configured policy URLs', function () {
    $links = loadPackageConfig([
        'COOKIE_CONSENT_PRIVACY_POLICY_URL' => 'https://legal.example.com/privacy',
        'COOKIE_CONSENT_TERMS_URL' => '/legal/terms',
    ])['policy_links'];

    expect($links[0]['link'])->toBe('https://legal.example.com/privacy')
        ->and($links[1]['link'])->toBe('/legal/terms');
});

it('does not call url() while loading the config, so config:cache stays portable', function () {
    $source = file_get_contents(__DIR__ . '/../../src/config/laravel-cookie-consent.php');

    expect($source)->not->toMatch('/\burl\(\s*[\'"]/');
});

it('resolves policy links to absolute URLs at render time', function () {
    expect(CookieConsent::policyLinks())->toBe([
        ['text' => 'Privacy Policy', 'link' => 'http://localhost/privacy-policy'],
        ['text' => 'Terms and Conditions', 'link' => 'http://localhost/terms-and-conditions'],
    ]);
});

it('keeps absolute and special policy links unchanged', function () {
    config(['laravel-cookie-consent.policy_links' => [
        ['text' => 'Privacy', 'link' => 'https://legal.example.com/privacy'],
        ['text' => 'Contact', 'link' => 'mailto:privacy@example.com'],
        ['text' => 'Anchor', 'link' => '#cookies'],
        ['text' => 'Relative', 'link' => '/legal/terms'],
    ]]);

    expect(array_column(CookieConsent::policyLinks(), 'link'))->toBe([
        'https://legal.example.com/privacy',
        'mailto:privacy@example.com',
        '#cookies',
        'http://localhost/legal/terms',
    ]);
});

it('skips policy links without text or link (e.g. older published configs)', function () {
    config(['laravel-cookie-consent.policy_links' => [
        ['text' => 'Privacy Policy', 'link' => ''],
        ['text' => '', 'link' => 'terms'],
        ['text' => 'Cookies', 'link' => 'cookie-policy'],
        'not-an-array',
    ]]);

    expect(CookieConsent::policyLinks())->toBe([
        ['text' => 'Cookies', 'link' => 'http://localhost/cookie-policy'],
    ]);
});

it('returns no policy links when policy_links is not an array', function () {
    config(['laravel-cookie-consent.policy_links' => null]);

    expect(CookieConsent::policyLinks())->toBe([]);
});

it('normalizes cookie lifetimes', function ($value, $expected) {
    config(['laravel-cookie-consent.cookie_lifetime' => $value]);

    expect(CookieConsent::cookieLifetime())->toBe($expected);
})->with([
    'integer' => [30, 30],
    'numeric string' => ['30', 30],
    'fractional days' => ['0.5', 0.5],
    'zero falls back' => [0, 365],
    'negative falls back' => [-1, 365],
    'non-numeric falls back' => ['forever', 365],
    'null falls back' => [null, 365],
]);

it('normalizes the reject lifetime', function ($value, $expected) {
    config(['laravel-cookie-consent.reject_lifetime' => $value]);

    expect(CookieConsent::rejectLifetime())->toBe($expected);
})->with([
    'integer' => [2, 2],
    'zero falls back' => [0, 7],
    'non-numeric falls back' => ['', 7],
]);
