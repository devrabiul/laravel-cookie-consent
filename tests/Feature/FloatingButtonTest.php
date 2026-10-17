<?php

use Devrabiul\CookieConsent\Facades\CookieConsent;

beforeEach(function () {
    config(['laravel-cookie-consent.cookie_prefix' => 'Laravel_App']);
});

it('is disabled by default', function () {
    $config = require __DIR__ . '/../../src/config/laravel-cookie-consent.php';

    expect($config['floating_button']['enabled'])->toBeFalse()
        ->and(CookieConsent::floatingButton())->toBeNull()
        ->and(CookieConsent::scripts()->render())->not->toContain('cookie-consent-floating-button');
});

it('renders a hidden button that opens the preferences modal', function () {
    config(['laravel-cookie-consent.floating_button.enabled' => true]);

    $html = CookieConsent::scripts()->render();

    expect($html)
        ->toContain('class="cookie-consent-floating-button cookie-consent-floating-bottom-left"')
        ->toContain('aria-label="Cookie settings"')
        ->toContain('aria-controls="cookie-preferences-modal"')
        ->toMatch('/cookie-consent-floating-button.*?\shidden>/s');
});

it('can be enabled from the environment', function () {
    $_ENV['COOKIE_CONSENT_FLOATING_BUTTON'] = $_SERVER['COOKIE_CONSENT_FLOATING_BUTTON'] = 'true';
    $_ENV['COOKIE_CONSENT_FLOATING_BUTTON_POSITION'] = $_SERVER['COOKIE_CONSENT_FLOATING_BUTTON_POSITION'] = 'bottom-right';

    try {
        $config = require __DIR__ . '/../../src/config/laravel-cookie-consent.php';
    } finally {
        unset($_ENV['COOKIE_CONSENT_FLOATING_BUTTON'], $_SERVER['COOKIE_CONSENT_FLOATING_BUTTON'],
            $_ENV['COOKIE_CONSENT_FLOATING_BUTTON_POSITION'], $_SERVER['COOKIE_CONSENT_FLOATING_BUTTON_POSITION']);
    }

    expect(CookieConsent::floatingButton($config))->toBe(['position' => 'bottom-right', 'label' => 'Cookie settings']);
});

it('normalizes the position and label', function ($settings, $expected) {
    config(['laravel-cookie-consent.floating_button' => $settings]);

    expect(CookieConsent::floatingButton())->toBe($expected);
})->with([
    'right' => [['enabled' => true, 'position' => 'bottom-right', 'label' => 'Privacy'], ['position' => 'bottom-right', 'label' => 'Privacy']],
    'invalid position' => [['enabled' => true, 'position' => 'top-center'], ['position' => 'bottom-left', 'label' => 'Cookie settings']],
    'empty label' => [['enabled' => true, 'label' => '  '], ['position' => 'bottom-left', 'label' => 'Cookie settings']],
    'string true' => [['enabled' => 'true'], ['position' => 'bottom-left', 'label' => 'Cookie settings']],
    'string false' => [['enabled' => 'false'], null],
    'not an array' => [true, null],
]);

it('accepts the settings as a render-time option', function () {
    $html = CookieConsent::scripts(['floating_button' => ['enabled' => true, 'position' => 'bottom-right', 'label' => 'Privacy choices']])->render();

    expect($html)
        ->toContain('cookie-consent-floating-bottom-right')
        ->toContain('aria-label="Privacy choices"');
});

it('escapes the label', function () {
    config(['laravel-cookie-consent.floating_button' => ['enabled' => true, 'label' => '"><script>alert(1)</script>']]);

    expect(CookieConsent::scripts()->render())->not->toContain('<script>alert(1)</script>');
});

it('uses the card colors', function () {
    config([
        'laravel-cookie-consent.floating_button.enabled' => true,
        'laravel-cookie-consent.card_bg_color' => '#0f172a',
    ]);

    expect(substr_count(CookieConsent::scripts()->render(), 'style="--cc-card-bg-color: #0f172a"'))->toBe(3);
});
