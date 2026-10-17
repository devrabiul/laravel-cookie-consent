<?php

use Illuminate\Http\Request;

function bindConsentRequest(?array $categories, string $status = 'accepted', string $version = '1.0'): void
{
    $cookies = [];
    if ($categories !== null) {
        $cookies['laravel-app_consent_preferences'] = json_encode([
            'version' => $version,
            'status' => $status,
            'timestamp' => time() - 60,
            'categories' => (object)$categories,
        ]);
    }

    app()->instance('request', Request::create('/', 'GET', [], $cookies));
}

beforeEach(function () {
    config([
        'laravel-cookie-consent.cookie_prefix' => 'Laravel_App',
        'laravel-cookie-consent.cookie_categories.analytics.enabled' => true,
        'laravel-cookie-consent.cookie_categories.marketing.enabled' => true,
    ]);
});

it('renders the content when the visitor consented to the category', function () {
    bindConsentRequest(['analytics' => true]);

    expect(view('consent-simple')->render())
        ->toContain('<script src="https://analytics.example.com/a.js"></script>');
});

it('hides the content without consent', function (?array $categories, string $status, string $version) {
    bindConsentRequest($categories, $status, $version);

    expect(trim(view('consent-simple')->render()))->toBe('');
})->with([
    'no cookie' => [null, 'accepted', '1.0'],
    'category declined' => [['analytics' => false], 'accepted', '1.0'],
    'rejected' => [['analytics' => true], 'rejected', '1.0'],
    'outdated version' => [['analytics' => true], 'accepted', '0.1'],
]);

it('hides the content when the cookie is malformed', function () {
    app()->instance('request', Request::create('/', 'GET', [], [
        'laravel-app_consent_preferences' => '{"version":"1.0",',
    ]));

    expect(trim(view('consent-simple')->render()))->toBe('');
});

it('supports @elsecookieConsent and @else', function (?array $categories, string $expected) {
    bindConsentRequest($categories);

    expect(trim(view('consent-else')->render()))->toBe($expected);
})->with([
    'analytics' => [['analytics' => true, 'marketing' => true], 'ANALYTICS'],
    'marketing only' => [['analytics' => false, 'marketing' => true], 'MARKETING'],
    'none' => [null, 'NONE'],
]);

it('handles unknown categories, non-string arguments and locked categories', function () {
    bindConsentRequest(['unknown' => true, 'analytics' => true]);

    expect(trim(view('consent-invalid')->render()))->toBe('[][][NECESSARY ]');
});

it('evaluates consent on every render, not when the view is compiled', function () {
    bindConsentRequest(['analytics' => true]);
    expect(view('consent-simple')->render())->toContain('a.js');

    bindConsentRequest(['analytics' => false]);
    expect(view('consent-simple')->render())->not->toContain('a.js');
});
