<?php

use Devrabiul\CookieConsent\Facades\CookieConsent;
use Devrabiul\CookieConsent\Tests\ConsentFixtures;

/*
 * Scenarios shared with tests/js/consent.test.mjs, so the server-side API and the
 * banner's JavaScript agree on whether consent is current and what it grants.
 */

function applyFixtureConfig(int $index): void
{
    config(['laravel-cookie-consent' => array_merge(
        config('laravel-cookie-consent'),
        ConsentFixtures::config($index)
    )]);
}

it('evaluates consent from the cookie bag', function (int $index) {
    applyFixtureConfig($index);
    $expected = ConsentFixtures::case($index)['expected'];
    $request = ConsentFixtures::request($index);

    expect(CookieConsent::hasValidConsent($request))->toBe($expected['valid']);

    foreach (['necessary', 'analytics', 'marketing', 'preferences'] as $category) {
        expect(CookieConsent::hasConsent($category, $request))->toBe($expected[$category], $category);
    }
})->with(ConsentFixtures::dataset());

it('evaluates consent from the raw cookie header', function (int $index) {
    applyFixtureConfig($index);
    $expected = ConsentFixtures::case($index)['expected'];
    $request = ConsentFixtures::request($index, true);

    expect(CookieConsent::hasValidConsent($request))->toBe($expected['valid']);

    foreach (['necessary', 'analytics', 'marketing', 'preferences'] as $category) {
        expect(CookieConsent::hasConsent($category, $request))->toBe($expected[$category], $category);
    }
})->with(ConsentFixtures::dataset());

it('reports the matching Google consent state', function (int $index) {
    applyFixtureConfig($index);
    config(['laravel-cookie-consent.google_consent_mode' => ['enabled' => true]]);
    $expected = ConsentFixtures::case($index)['expected'];
    $state = fn (string $category) => $expected[$category] ? 'granted' : 'denied';

    expect(CookieConsent::googleConsentState(ConsentFixtures::request($index)))->toBe([
        'ad_storage' => $state('marketing'),
        'ad_user_data' => $state('marketing'),
        'ad_personalization' => $state('marketing'),
        'analytics_storage' => $state('analytics'),
        'functionality_storage' => $state('preferences'),
        'personalization_storage' => $state('preferences'),
        'security_storage' => 'granted',
    ]);
})->with(ConsentFixtures::dataset());
