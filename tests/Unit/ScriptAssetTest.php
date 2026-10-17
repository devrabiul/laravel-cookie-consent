<?php

/*
 * Static regression checks on the shipped JavaScript. Behaviour is covered by
 * tests/js/consent.test.mjs (run with `composer test:js`).
 */

beforeEach(function () {
    $this->script = file_get_contents(__DIR__ . '/../../assets/js/script.js');
});

it('does not hard-code a cookie expiration', function () {
    expect($this->script)->not->toMatch('/365\s*\*\s*24/')
        ->toContain("data-cookie-lifetime")
        ->toContain("data-reject-lifetime");
});

it('never assigns window.onload', function () {
    expect($this->script)->not->toMatch('/window\.onload\s*=/');
});

it('keeps validation limits in sync with the PHP implementation', function () {
    $php = new ReflectionClass(\Devrabiul\CookieConsent\CookieConsent::class);

    expect($this->script)
        ->toContain('const MAX_COOKIE_LENGTH = ' . $php->getConstant('MAX_COOKIE_LENGTH') . ';')
        ->toContain('const CLOCK_SKEW_TOLERANCE = ' . $php->getConstant('CLOCK_SKEW_TOLERANCE') . ';');
});
