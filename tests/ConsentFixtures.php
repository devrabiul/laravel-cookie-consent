<?php

namespace Devrabiul\CookieConsent\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Loads tests/fixtures/consent-cases.json, the consent scenarios shared with the
 * JavaScript test suite, and turns them into cookies the way the browser sends them.
 */
class ConsentFixtures
{
    /**
     * The fixture file as associative arrays.
     */
    public static function load(): array
    {
        return json_decode(self::contents(), true);
    }

    /**
     * Case names keyed for use as a Pest dataset: name => [index].
     */
    public static function dataset(): array
    {
        $dataset = [];
        foreach (self::load()['cases'] as $index => $case) {
            $dataset[$case['name']] = [$index];
        }

        return $dataset;
    }

    public static function case(int $index): array
    {
        return self::load()['cases'][$index];
    }

    /**
     * Package configuration for a case.
     */
    public static function config(int $index): array
    {
        $fixtures = self::load();
        $categories = [];
        foreach ($fixtures['categories'] as $key => $definition) {
            $categories[$key] = $definition + ['title' => ucfirst($key), 'description' => ''];
        }

        return array_merge([
            'cookie_prefix' => $fixtures['cookie_prefix'],
            'consent_version' => '1.0',
            'cookie_lifetime' => 365,
            'reject_lifetime' => 7,
            'cookie_categories' => $categories,
        ], $fixtures['cases'][$index]['config'] ?? []);
    }

    /**
     * Decoded cookie values keyed by cookie name, as PHP exposes them in $_COOKIE.
     */
    public static function cookies(int $index, ?int $now = null): array
    {
        $now = $now ?? time();
        // Decode as objects so "{}" and "[]" in records stay distinct when re-encoded
        $case = json_decode(self::contents())->cases[$index];
        $slug = Str::slug(self::load()['cookie_prefix']);
        $year = (int)date('Y', $now);
        $names = [
            '{consent}' => $slug . '_consent',
            '{preferences}' => $slug . '_consent_preferences',
            '{legacy}' => $slug . '_' . $year,
            '{legacy_preferences}' => $slug . '_' . $year . '_preferences',
            '{legacy_previous}' => $slug . '_' . ($year - 1),
            '{legacy_previous_preferences}' => $slug . '_' . ($year - 1) . '_preferences',
        ];

        $cookies = [];
        foreach (get_object_vars($case->cookies) as $token => $spec) {
            $cookies[$names[$token]] = property_exists($spec, 'raw')
                ? $spec->raw
                : self::encodeRecord($spec->json, $now);
        }

        return $cookies;
    }

    /**
     * Build a request carrying the case's cookies, either in the cookie bag or only in
     * the raw Cookie header (URL-encoded like the banner's JavaScript writes them).
     */
    public static function request(int $index, bool $headerOnly = false): Request
    {
        $cookies = self::cookies($index);

        if (!$headerOnly) {
            return Request::create('/', 'GET', [], $cookies);
        }

        $header = [];
        foreach ($cookies as $name => $value) {
            $header[] = $name . '=' . rawurlencode($value);
        }

        return Request::create('/', 'GET', [], [], [], ['HTTP_COOKIE' => implode('; ', $header)]);
    }

    /**
     * Encode a consent record object, resolving the fixture-only helper fields.
     */
    public static function encodeRecord(object $record, int $now): string
    {
        $record = clone $record;
        if (property_exists($record, 'age_days')) {
            $record->timestamp = (int)floor($now - $record->age_days * 86400);
            unset($record->age_days);
        }
        if (property_exists($record, 'padding')) {
            $record->padding = str_repeat('x', $record->padding);
        }

        return json_encode($record);
    }

    private static function contents(): string
    {
        return file_get_contents(__DIR__ . '/fixtures/consent-cases.json');
    }
}
