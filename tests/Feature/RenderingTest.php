<?php

use Devrabiul\CookieConsent\Facades\CookieConsent;

function renderBanner(array $options = []): string
{
    $view = CookieConsent::scripts($options);

    return is_string($view) ? $view : $view->render();
}

beforeEach(function () {
    config(['laravel-cookie-consent.cookie_prefix' => 'Laravel_App']);
});

it('renders the banner with stable, versioned cookie attributes', function () {
    $html = renderBanner();

    expect($html)
        ->toContain('data-cookie-prefix="laravel-app_consent"')
        ->toContain('data-consent-version="1.0"')
        ->toContain('data-cookie-lifetime="365"')
        ->toContain('data-reject-lifetime="7"')
        ->toContain('data-legacy-cookie-prefix="laravel-app_' . date('Y') . '"')
        ->toContain('data-legacy-consent-version="1.0"')
        ->not->toContain('data-cookie-prefix="laravel-app_' . date('Y') . '"');
});

it('passes configured lifetimes and version to the JavaScript', function () {
    config([
        'laravel-cookie-consent.cookie_lifetime' => 30,
        'laravel-cookie-consent.reject_lifetime' => 2,
        'laravel-cookie-consent.consent_version' => '2.0',
    ]);

    expect(renderBanner())
        ->toContain('data-cookie-lifetime="30"')
        ->toContain('data-reject-lifetime="2"')
        ->toContain('data-consent-version="2.0"');
});

it('applies render-time option overrides', function () {
    expect(renderBanner(['cookie_lifetime' => 90, 'reject_lifetime' => 1]))
        ->toContain('data-cookie-lifetime="90"')
        ->toContain('data-reject-lifetime="1"');
});

it('falls back to default lifetimes for invalid values', function () {
    expect(renderBanner(['cookie_lifetime' => 'forever', 'reject_lifetime' => -3]))
        ->toContain('data-cookie-lifetime="365"')
        ->toContain('data-reject-lifetime="7"');
});

it('escapes the consent version attribute', function () {
    config(['laravel-cookie-consent.consent_version' => '"><script>alert(1)</script>']);

    expect(renderBanner())
        ->not->toContain('<script>alert(1)</script>')
        ->toContain('&quot;&gt;&lt;script&gt;');
});

it('renders the policy links with the URL fallback', function () {
    expect(renderBanner())
        ->toContain('href="http://localhost/privacy-policy"')
        ->toContain('href="http://localhost/terms-and-conditions"');
});

it('renders explicitly configured policy URLs', function () {
    $html = renderBanner(['policy_links' => [
        ['text' => 'Privacy', 'link' => 'https://legal.example.com/privacy'],
    ]]);

    expect($html)
        ->toContain('href="https://legal.example.com/privacy"')
        ->not->toContain('terms-and-conditions');
});

it('omits the links container when there are no usable links', function () {
    expect(renderBanner(['policy_links' => [['text' => 'Privacy', 'link' => '']]]))
        ->not->toContain('cookie-consent-links-container')
        ->not->toContain('href=""');
});

it('renders nothing when the banner is disabled', function ($enabled) {
    config(['laravel-cookie-consent.enabled' => $enabled]);

    expect(CookieConsent::scripts())->toBe('');
})->with([false, 'false']);

it('no longer requests the script-utils endpoint or touches window.onload', function () {
    expect(renderBanner())
        ->not->toContain('script-utils')
        ->not->toContain('window.onload')
        ->toContain('assets/js/script.js');
});

it('looks up categories by their exact key in the service loader', function () {
    config([
        'laravel-cookie-consent.cookie_categories.analytics.enabled' => true,
        'laravel-cookie-consent.cookie_categories.ad-tracking' => [
            'enabled' => true,
            'locked' => false,
            'js_action' => 'loadAds',
            'title' => 'Ads',
            'description' => '',
        ],
    ]);

    expect(renderBanner())
        ->toContain('preferences["analytics"] === true')
        ->toContain('preferences["ad-tracking"] === true')
        ->toContain('const action = "loadAds";')
        ->toContain('data-category="ad-tracking"');
});

it('does not write translation or other application files while rendering', function () {
    $langFile = resource_path('lang/' . app()->getLocale() . '/messages.php');
    $existedBefore = file_exists($langFile);

    renderBanner();

    expect(file_exists($langFile))->toBe($existedBefore);
});

it('applies the configured card background color to the banner and the modal', function ($color) {
    config(['laravel-cookie-consent.card_bg_color' => $color]);

    expect(substr_count(renderBanner(), 'style="--cc-card-bg-color: ' . trim($color) . '"'))->toBe(2);
})->with([
    'hex' => ['#f8fafc'],
    'short hex' => ['#fff'],
    'hex with alpha' => ['#ffffffcc'],
    'rgb' => ['rgb(248, 250, 252)'],
    'rgba' => ['rgba(0, 0, 0, 0.5)'],
    'hsl' => ['hsl(210 40% 98%)'],
    'named' => ['white'],
    'surrounding whitespace' => ['  #f8fafc  '],
]);

it('accepts the card background color as a render-time option', function () {
    expect(renderBanner(['card_bg_color' => '#123456']))
        ->toContain('style="--cc-card-bg-color: #123456"');
});

it('uses the theme color when no card background color is set', function ($color) {
    config(['laravel-cookie-consent.card_bg_color' => $color]);

    expect(renderBanner())->not->toContain('--cc-card-bg-color');
})->with([
    'null' => [null],
    'empty' => [''],
    'injection attempt' => ['red; background-image: url(https://evil.example)'],
    'markup' => ['"><script>alert(1)</script>'],
    'invalid hex' => ['#12345'],
    'css function' => ['var(--x)'],
    'non-string' => [['#fff']],
]);

it('applies the configured card text color to the banner and the modal', function () {
    config(['laravel-cookie-consent.card_text_color' => '#0f172a']);

    $html = renderBanner();

    expect(substr_count($html, 'style="--cc-card-text-color: #0f172a"'))->toBe(2)
        ->and($html)->not->toContain('cookie-consent-custom-bg');
});

it('combines the card background and text colors', function () {
    config([
        'laravel-cookie-consent.card_bg_color' => '#0f172a',
        'laravel-cookie-consent.card_text_color' => 'rgb(248, 250, 252)',
    ]);

    expect(substr_count(renderBanner(), 'style="--cc-card-bg-color: #0f172a; --cc-card-text-color: rgb(248, 250, 252)"'))->toBe(2);
});

it('ignores an invalid card text color', function () {
    config(['laravel-cookie-consent.card_text_color' => 'red; display: none']);

    expect(renderBanner())->not->toContain('--cc-card-text-color');
});

it('lets the links strip follow a custom card background', function () {
    config(['laravel-cookie-consent.card_bg_color' => '#0f172a']);
    expect(renderBanner())->toContain('cookie-consent-custom-bg');

    config(['laravel-cookie-consent.card_bg_color' => null]);
    expect(renderBanner())->not->toContain('cookie-consent-custom-bg');
});

it('calls each js_action at most once per page (SPA navigation)', function () {
    config(['laravel-cookie-consent.cookie_categories.analytics.enabled' => true]);

    expect(renderBanner())
        ->toContain('window.cookieConsentLoadedActions = window.cookieConsentLoadedActions || {};')
        ->toContain('!window.cookieConsentLoadedActions[action]')
        ->not->toContain("addEventListener('DOMContentLoaded'");
});

it('applies the theme preset without waiting for DOMContentLoaded', function () {
    config(['laravel-cookie-consent.theme_preset' => 'modern-blue']);

    expect(renderBanner())->toContain('document.documentElement.setAttribute(\'cookie-consent-theme-preset\', "modern-blue");');
});
