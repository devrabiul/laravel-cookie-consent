<?php

namespace Devrabiul\CookieConsent;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager as Session;
use Illuminate\Config\Repository as Config;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Class CookieConsent
 *
 * Handles the display and management of cookie consent UI components in a Laravel application.
 */
class CookieConsent
{
    /**
     * Consent version used when `consent_version` is not configured.
     */
    public const DEFAULT_CONSENT_VERSION = '1.0';

    /**
     * Consent version assigned to cookies written by releases before consent versioning
     * existed (the year-based `{prefix}_{year}` cookies). They are honoured only while the
     * configured `consent_version` equals this value.
     */
    public const LEGACY_CONSENT_VERSION = '1.0';

    /**
     * Default lifetime, in days, of an accepted / customised consent.
     */
    public const DEFAULT_COOKIE_LIFETIME = 365;

    /**
     * Default lifetime, in days, of a rejected consent.
     */
    public const DEFAULT_REJECT_LIFETIME = 7;

    /**
     * Maximum accepted length of the raw preferences cookie value.
     */
    protected const MAX_COOKIE_LENGTH = 4096;

    /**
     * Allowed clock difference, in seconds, between the browser that wrote the
     * consent timestamp and the server validating it.
     */
    protected const CLOCK_SKEW_TOLERANCE = 86400;

    /**
     * Google Consent Mode v2 consent types and the cookie category each one follows
     * by default.
     */
    public const DEFAULT_GOOGLE_CONSENT_TYPES = [
        'ad_storage' => 'marketing',
        'ad_user_data' => 'marketing',
        'ad_personalization' => 'marketing',
        'analytics_storage' => 'analytics',
        'functionality_storage' => 'preferences',
        'personalization_storage' => 'preferences',
        'security_storage' => 'necessary',
    ];

    /**
     * The session manager.
     *
     * @var \Illuminate\Session\SessionManager
     */
    protected $session;

    /**
     * The Config handler instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The JavaScript type for script tags.
     *
     * @var string
     */
    protected string $jsType = 'text/javascript';

    /**
     * CookieConsent constructor.
     *
     * @param Session $session The session manager instance.
     * @param Config $config The configuration repository instance.
     */
    function __construct(Session $session, Config $config)
    {
        $this->session = $session;
        $this->config = $config;
    }

    /**
     * Generate the HTML for the required styles.
     *
     * @return string The HTML link tag for the stylesheet.
     */
    public function styles(): string
    {
        $stylePath = 'packages/devrabiul/laravel-cookie-consent/css/style.css';
        if (File::exists(public_path($stylePath))) {
            $link = '<link rel="stylesheet" href="' . $this->getDynamicAsset($stylePath) . '">';
        } else {
            $link = '<link rel="stylesheet" href="' . url('vendor/devrabiul/laravel-cookie-consent/assets/css/style.css') . '">';
        }

        // Google Consent Mode defaults belong in <head>, before any Google tag
        return $link . $this->googleConsentMode();
    }

    /**
     * Generate the Google Consent Mode v2 snippet for <head>.
     *
     * It sets the default consent state ("denied", "granted" for locked categories) and
     * applies the visitor's stored consent before Google tags load. Place it above
     * gtag.js / Google Tag Manager. styles() includes it automatically; it is output at
     * most once per request, so calling it explicitly as well is safe.
     *
     * @return string Empty when Consent Mode or the banner is disabled.
     */
    public function googleConsentMode(): string
    {
        $config = $this->resolveConfig(null);
        $types = $this->googleConsentTypes($config);

        if ($types === null || !$this->isBannerEnabled($config)) {
            return '';
        }

        $request = $this->currentRequest();
        if ($request !== null) {
            if ($request->attributes->get('cookie_consent_google_consent_mode_rendered')) {
                return '';
            }
            $request->attributes->set('cookie_consent_google_consent_mode_rendered', true);
        }

        $settings = (array)($config['google_consent_mode'] ?? []);
        $categories = is_array($config['cookie_categories'] ?? null) ? $config['cookie_categories'] : [];
        $enabled = $granted = [];
        foreach ($categories as $key => $definition) {
            if (is_array($definition) && !empty($definition['enabled'])) {
                $enabled[] = (string)$key;
                if (!empty($definition['locked'])) {
                    $granted[] = (string)$key;
                }
            }
        }

        $data = [
            'cookieName' => $this->preferencesCookieName($config),
            'version' => $this->consentVersion($config),
            'cookieLifetime' => $this->cookieLifetime($config),
            'rejectLifetime' => $this->rejectLifetime($config),
            'maxCookieLength' => self::MAX_COOKIE_LENGTH,
            'clockSkewTolerance' => self::CLOCK_SKEW_TOLERANCE,
            'types' => (object)$types,
            'enabledCategories' => $enabled,
            'grantedCategories' => $granted,
            'waitForUpdate' => max(0, (int)($settings['wait_for_update'] ?? 500)),
            'adsDataRedaction' => filter_var($settings['ads_data_redaction'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'urlPassthrough' => filter_var($settings['url_passthrough'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];

        $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
        $script = file_get_contents(__DIR__ . '/../resources/js/google-consent-mode.js');

        return "<script>window.cookieConsentGoogleConsentMode = {$json};\n{$script}</script>";
    }

    /**
     * Get the Google consent state for the visitor, e.g. for server-side tagging or the
     * Measurement Protocol. Matches what the banner sends to Consent Mode.
     *
     * @param Request|null $request Defaults to the current request.
     * @return array<string, string> Consent type => "granted" | "denied"; empty when
     *         Consent Mode is disabled.
     */
    public function googleConsentState(?Request $request = null): array
    {
        $types = $this->googleConsentTypes();
        if ($types === null) {
            return [];
        }

        $categories = (array)$this->config->get('laravel-cookie-consent.cookie_categories');
        $state = [];
        foreach ($types as $type => $category) {
            $definition = $categories[$category] ?? null;
            $locked = is_array($definition) && !empty($definition['enabled']) && !empty($definition['locked']);
            $state[$type] = $locked || $this->hasConsent($category, $request) ? 'granted' : 'denied';
        }

        return $state;
    }

    /**
     * Get the Google consent types mapped to cookie categories, or null when Google
     * Consent Mode is disabled. Unknown consent types are ignored.
     *
     * @param array|null $config Configuration to use, defaults to the package config.
     * @return array<string, string>|null
     */
    public function googleConsentTypes(?array $config = null): ?array
    {
        $settings = $this->resolveConfig($config)['google_consent_mode'] ?? null;
        if (!is_array($settings) || !filter_var($settings['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        $mapping = is_array($settings['consent_types'] ?? null)
            ? $settings['consent_types']
            : self::DEFAULT_GOOGLE_CONSENT_TYPES;

        $types = [];
        foreach ($mapping as $type => $category) {
            if (array_key_exists($type, self::DEFAULT_GOOGLE_CONSENT_TYPES) && is_string($category) && $category !== '') {
                $types[$type] = $category;
            }
        }

        return $types;
    }

    /**
     * Get the floating "Cookie settings" button settings, or null when it is disabled.
     *
     * @param array|null $config Configuration to use, defaults to the package config.
     * @return array{position: string, label: string}|null
     */
    public function floatingButton(?array $config = null): ?array
    {
        $settings = $this->resolveConfig($config)['floating_button'] ?? null;
        if (!is_array($settings) || !filter_var($settings['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        $position = $settings['position'] ?? null;
        $label = is_string($settings['label'] ?? null) ? trim($settings['label']) : '';

        return [
            'position' => in_array($position, ['bottom-left', 'bottom-right'], true) ? $position : 'bottom-left',
            'label' => $label !== '' ? $label : 'Cookie settings',
        ];
    }

    /**
     * Determine whether the banner is enabled.
     *
     * @param array $config
     * @return bool
     */
    protected function isBannerEnabled(array $config): bool
    {
        return !isset($config['enabled']) || ($config['enabled'] !== false && $config['enabled'] !== 'false');
    }

    /**
     * Render the cookie consent view with the given configuration.
     *
     * @param array $cookieConfig Optional cookie configuration overrides.
     * @return View The cookie consent view.
     */
    public function content(array $cookieConfig = [])
    {
        return view('laravel-cookie-consent::cookie-consent', [
            'cookieConfig' => $cookieConfig,
            'consentCookieName' => $this->consentCookieName($cookieConfig),
            'consentVersion' => $this->consentVersion($cookieConfig),
            'cookieLifetime' => $this->cookieLifetime($cookieConfig),
            'rejectLifetime' => $this->rejectLifetime($cookieConfig),
            'legacyCookieName' => $this->legacyCookieName($cookieConfig),
            'legacyConsentVersion' => self::LEGACY_CONSENT_VERSION,
            'policyLinks' => $this->policyLinks($cookieConfig),
            'cardBgColor' => $this->cardBackgroundColor($cookieConfig),
            'cardStyle' => $this->cardStyle($cookieConfig),
            'floatingButton' => $this->floatingButton($cookieConfig),
            'googleConsentTypes' => $this->googleConsentTypes($cookieConfig),
        ]);
    }

    /**
     * Generate the HTML for the required JavaScript with optional configuration overrides.
     *
     * @param array $options Optional configuration overrides.
     * @return View The cookie consent script view.
     */
    public function scripts(array $options = []): mixed
    {
        $config = (array)$this->config->get('laravel-cookie-consent');
        $config = array_merge($config, $options);
        if (!$this->isBannerEnabled($config)) {
            return '';
        }
        return self::content(cookieConfig: $config);
    }

    /**
     * Generate the HTML for the required scripts.
     *
     * @return string The HTML link tag for the scripts.
     */
    public function scriptsPath(): string
    {
        $script = $this->scriptTag('vendor/devrabiul/laravel-cookie-consent/assets/js/script.js');
        $defaultJsPath = 'packages/devrabiul/laravel-cookie-consent/js/script.js';
        if (File::exists(public_path($defaultJsPath))) {
            $script = $this->scriptTag($defaultJsPath);
        }

        return $script;
    }

    /**
     * Determine whether the visitor has consented to the given cookie category.
     *
     * Consent is read from the package's preferences cookie (written by the banner's
     * JavaScript) and is only honoured when the stored record is well-formed, matches the
     * configured `consent_version` and has not outlived its configured lifetime.
     *
     * - Unknown or disabled categories always return false.
     * - Locked categories (e.g. "necessary") return true once a valid consent decision
     *   exists, regardless of the stored value.
     * - Rejected consent only grants locked categories.
     *
     * @param string $category The category key from `cookie_categories`, e.g. "analytics".
     * @param Request|null $request Defaults to the current request.
     * @return bool
     */
    public function hasConsent(string $category, ?Request $request = null): bool
    {
        $categories = $this->config->get('laravel-cookie-consent.cookie_categories');
        $definition = is_array($categories) ? ($categories[$category] ?? null) : null;

        // Same truthiness as the Blade view uses to decide whether a category is rendered.
        if (!is_array($definition) || empty($definition['enabled'])) {
            return false;
        }

        $record = $this->consentRecord($request);
        if ($record === null) {
            return false;
        }

        if (!empty($definition['locked'])) {
            return true;
        }

        if ($record['status'] !== 'accepted') {
            return false;
        }

        return ($record['categories'][$category] ?? null) === true;
    }

    /**
     * Determine whether the visitor has made a consent decision (accepted, rejected or
     * customised) that is still current. When this returns false the banner is shown.
     *
     * @param Request|null $request Defaults to the current request.
     * @return bool
     */
    public function hasValidConsent(?Request $request = null): bool
    {
        return $this->consentRecord($request) !== null;
    }

    /**
     * Get the visitor's validated consent record.
     *
     * @param Request|null $request Defaults to the current request.
     * @return array{version: string, status: string, timestamp: int|null, categories: array}|null
     *         Null when there is no current, valid consent.
     */
    public function consentRecord(?Request $request = null): ?array
    {
        $request = $request ?? $this->currentRequest();
        if ($request === null) {
            return null;
        }

        $raw = $this->readCookie($request, $this->preferencesCookieName());

        if ($raw === null) {
            return $this->legacyConsentRecord($request);
        }

        $record = $this->decodeConsentRecord($raw);
        if ($record === null || $record['version'] !== $this->consentVersion()) {
            return null;
        }

        $lifetimeDays = $record['status'] === 'rejected' ? $this->rejectLifetime() : $this->cookieLifetime();
        $now = time();

        if ($record['timestamp'] > $now + self::CLOCK_SKEW_TOLERANCE
            || $now - $record['timestamp'] > $lifetimeDays * 86400) {
            return null;
        }

        return $record;
    }

    /**
     * Get the name of the consent status cookie ("accepted" / "rejected").
     *
     * The name is derived from `cookie_prefix` only, so it stays stable across releases,
     * years and consent versions.
     *
     * @param array|null $config Configuration to use, defaults to the package config.
     * @return string
     */
    public function consentCookieName(?array $config = null): string
    {
        $slug = Str::slug((string)($this->resolveConfig($config)['cookie_prefix'] ?? ''));

        return ($slug !== '' ? $slug : 'cookie') . '_consent';
    }

    /**
     * Get the name of the cookie holding the versioned consent record.
     *
     * @param array|null $config Configuration to use, defaults to the package config.
     * @return string
     */
    public function preferencesCookieName(?array $config = null): string
    {
        return $this->consentCookieName($config) . '_preferences';
    }

    /**
     * Get the configured consent version as a non-empty string.
     *
     * @param array|null $config Configuration to use, defaults to the package config.
     * @return string
     */
    public function consentVersion(?array $config = null): string
    {
        $version = $this->resolveConfig($config)['consent_version'] ?? null;
        $version = is_scalar($version) ? trim((string)$version) : '';

        return $version !== '' ? $version : self::DEFAULT_CONSENT_VERSION;
    }

    /**
     * Get the lifetime, in days, of an accepted or customised consent.
     *
     * @param array|null $config Configuration to use, defaults to the package config.
     * @return int|float
     */
    public function cookieLifetime(?array $config = null): int|float
    {
        return $this->normalizeLifetime(
            $this->resolveConfig($config)['cookie_lifetime'] ?? null,
            self::DEFAULT_COOKIE_LIFETIME
        );
    }

    /**
     * Get the lifetime, in days, of a rejected consent.
     *
     * @param array|null $config Configuration to use, defaults to the package config.
     * @return int|float
     */
    public function rejectLifetime(?array $config = null): int|float
    {
        return $this->normalizeLifetime(
            $this->resolveConfig($config)['reject_lifetime'] ?? null,
            self::DEFAULT_REJECT_LIFETIME
        );
    }

    /**
     * Resolve the configured policy links into absolute URLs.
     *
     * Relative paths (e.g. "privacy-policy") are resolved with url() at render time, so
     * the fallback works with `config:cache`. Absolute URLs are kept unchanged. Entries
     * without text or link are skipped.
     *
     * @param array|null $config Configuration to use, defaults to the package config.
     * @return array<int, array{text: string, link: string}>
     */
    public function policyLinks(?array $config = null): array
    {
        $links = $this->resolveConfig($config)['policy_links'] ?? [];
        if (!is_array($links)) {
            return [];
        }

        $resolved = [];
        foreach ($links as $item) {
            $text = is_array($item) ? trim((string)($item['text'] ?? '')) : '';
            $link = is_array($item) ? trim((string)($item['link'] ?? '')) : '';

            if ($text === '' || $link === '') {
                continue;
            }

            $resolved[] = ['text' => $text, 'link' => url($link)];
        }

        return $resolved;
    }

    /**
     * Get the configured card background color, or null to use the theme's color.
     *
     * @param array|null $config Configuration to use, defaults to the package config.
     * @return string|null
     */
    public function cardBackgroundColor(?array $config = null): ?string
    {
        return $this->colorValue($this->resolveConfig($config)['card_bg_color'] ?? null);
    }

    /**
     * Get the configured card text color, or null to use the theme's color.
     *
     * @param array|null $config Configuration to use, defaults to the package config.
     * @return string|null
     */
    public function cardTextColor(?array $config = null): ?string
    {
        return $this->colorValue($this->resolveConfig($config)['card_text_color'] ?? null);
    }

    /**
     * Build the inline CSS custom properties for the configured card colors.
     *
     * @param array|null $config Configuration to use, defaults to the package config.
     * @return string Empty when no custom color is configured.
     */
    public function cardStyle(?array $config = null): string
    {
        $properties = array_filter([
            '--cc-card-bg-color' => $this->cardBackgroundColor($config),
            '--cc-card-text-color' => $this->cardTextColor($config),
        ]);

        $declarations = [];
        foreach ($properties as $property => $value) {
            $declarations[] = $property . ': ' . $value;
        }

        return implode('; ', $declarations);
    }

    /**
     * Validate a configured color.
     *
     * The value is placed in an inline style, so only plain color values are accepted
     * (hex, rgb()/rgba(), hsl()/hsla() or a named color); anything else returns null.
     *
     * @param mixed $color
     * @return string|null
     */
    protected function colorValue(mixed $color): ?string
    {
        $color = is_string($color) ? trim($color) : '';

        $pattern = '/^(#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})|(?:rgba?|hsla?)\(\s*[0-9.,%\s\/]+\)|[a-z]+)$/i';

        return preg_match($pattern, $color) ? $color : null;
    }

    /**
     * Get the name of the year-based status cookie written before consent versioning.
     *
     * @param array|null $config Configuration to use, defaults to the package config.
     * @return string
     */
    protected function legacyCookieName(?array $config = null): string
    {
        return Str::slug((string)($this->resolveConfig($config)['cookie_prefix'] ?? '')) . '_' . date('Y');
    }

    /**
     * Build a consent record from the year-based cookies written before consent
     * versioning, so upgrading the package does not ask every visitor again.
     *
     * Legacy consent counts as version LEGACY_CONSENT_VERSION; once `consent_version`
     * is changed it is no longer honoured. Its expiry is enforced by the browser.
     *
     * @param Request $request
     * @return array|null
     */
    protected function legacyConsentRecord(Request $request): ?array
    {
        if ($this->consentVersion() !== self::LEGACY_CONSENT_VERSION) {
            return null;
        }

        $legacyName = $this->legacyCookieName();
        $status = $this->readCookie($request, $legacyName);
        if ($status !== 'accepted' && $status !== 'rejected') {
            return null;
        }

        $categories = [];
        $rawPreferences = $this->readCookie($request, $legacyName . '_preferences');
        if ($rawPreferences !== null && strlen($rawPreferences) <= self::MAX_COOKIE_LENGTH) {
            $decoded = json_decode($rawPreferences, false, 8);
            if ($decoded instanceof \stdClass) {
                $categories = array_filter(get_object_vars($decoded), 'is_bool');
            }
        }

        return [
            'version' => self::LEGACY_CONSENT_VERSION,
            'status' => $status,
            'timestamp' => null,
            'categories' => $categories,
        ];
    }

    /**
     * Decode and structurally validate a raw consent record cookie value.
     *
     * @param string $raw
     * @return array|null Null when the value is malformed.
     */
    protected function decodeConsentRecord(string $raw): ?array
    {
        if ($raw === '' || strlen($raw) > self::MAX_COOKIE_LENGTH) {
            return null;
        }

        // Decode objects as stdClass so JSON objects and arrays can be told apart,
        // matching the validation done by the banner's JavaScript.
        $data = json_decode($raw, false, 8);
        $timestamp = $data->timestamp ?? null;

        if (!$data instanceof \stdClass
            || !is_string($data->version ?? null)
            || !in_array($data->status ?? null, ['accepted', 'rejected'], true)
            || !(is_int($timestamp) || (is_float($timestamp) && floor($timestamp) === $timestamp))
            || $timestamp <= 0
            || !($data->categories ?? null) instanceof \stdClass) {
            return null;
        }

        return [
            'version' => $data->version,
            'status' => $data->status,
            'timestamp' => (int)$timestamp,
            'categories' => array_filter(get_object_vars($data->categories), 'is_bool'),
        ];
    }

    /**
     * Read a raw cookie value written by the banner's JavaScript.
     *
     * Laravel's EncryptCookies middleware replaces cookies it cannot decrypt with null,
     * and these cookies are written unencrypted in the browser. In that case the value
     * is read from the raw Cookie header instead (decoded the same way PHP decodes
     * $_COOKIE). The value is validated by the caller.
     *
     * @param Request $request
     * @param string $name
     * @return string|null
     */
    protected function readCookie(Request $request, string $name): ?string
    {
        $value = $request->cookies->get($name);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        $header = $request->headers->get('cookie');
        if (!is_string($header) || $header === '') {
            return null;
        }

        foreach (explode(';', $header) as $pair) {
            $parts = explode('=', $pair, 2);
            if (count($parts) === 2 && trim($parts[0]) === $name) {
                $value = urldecode(trim($parts[1]));
                return $value !== '' ? $value : null;
            }
        }

        return null;
    }

    /**
     * Normalize a lifetime value to a positive number of days.
     *
     * @param mixed $value
     * @param int $default
     * @return int|float
     */
    protected function normalizeLifetime(mixed $value, int $default): int|float
    {
        if (is_numeric($value) && $value + 0 > 0) {
            return $value + 0;
        }

        return $default;
    }

    /**
     * Use the given configuration array, or the package configuration when null.
     *
     * @param array|null $config
     * @return array
     */
    protected function resolveConfig(?array $config): array
    {
        return $config ?? (array)$this->config->get('laravel-cookie-consent');
    }

    /**
     * Get the current HTTP request, if one is bound.
     *
     * @return Request|null
     */
    protected function currentRequest(): ?Request
    {
        $request = app()->bound('request') ? app('request') : null;

        return $request instanceof Request ? $request : null;
    }

    /**
     * Generate a script tag with the given source path.
     *
     * @param string $src
     * @return string
     */
    private function scriptTag(string $src): string
    {
        return '<script src="' . $this->getDynamicAsset($src) . '"></script>';
    }

    /**
     * Resolve the dynamic asset path depending on processing directory and environment.
     *
     * @param string $path
     * @return string
     */
    private function getDynamicAsset(string $path): string
    {

        $customBaseUrl = config('laravel-cookie-consent.asset_url');

        if (!empty($customBaseUrl)) {
            return rtrim($customBaseUrl, '/') . '/' . ltrim($path, '/');
        }

        if (config('laravel-cookie-consent.system_processing_directory') == 'public') {
            $position = strpos($path, 'public/');
            $result = $path;
            if ($position === 0) {
                $result = preg_replace('/public/', '', $path, 1);
            }
        } else if (
            (str_contains(realpath(public_path()), 'public\public') ||
            str_contains(realpath(public_path()), 'public/public')) &&
            PHP_OS_FAMILY === 'Windows'
        ) {
            $result = 'public/' . $path;
        } else {
            $result = in_array(request()->ip(), ['127.0.0.1']) && (config('laravel-cookie-consent.system_processing_directory') != 'root') ? $path : 'public/' . $path;
        }

        return asset($result);
    }

    public static function getRemoveInvalidCharacters($str): array|string
    {
        return str_ireplace(['"', ';', '<', '>'], ' ', preg_replace('/\s\s+/', ' ', $str));
    }

    /**
     * Translates a given key using the `messages.php` language file in the current locale.
     *
     * - If the key does not exist in the file, it creates the file and inserts the key with a default value.
     * - It ensures the required language directory and file exist.
     * - Falls back to Laravel translation helper if the key is not found after insertion.
     *
     * @param string $key The translation key, e.g. 'messages.privacy_policy'.
     * @param null $default The default value to use if the key doesn't exist.
     */
    public static function translate(string $key, $default = null)
    {
        $locale = app()->getLocale();
        $langDir = resource_path("lang/{$locale}");
        $filePath = "{$langDir}/messages.php";

        // Ensure the directory exists
        if (!File::exists($langDir)) {
            File::makeDirectory($langDir, 0755, true);
        }

        // If the file doesn't exist, create it with an empty array
        if (!File::exists($filePath)) {
            File::put($filePath, "<?php\n\nreturn [\n];");
        }

        try {
            // Load existing translations
            $translations = File::getRequire($filePath);

            if (!empty($key)) {
                $processedKey = str_replace('_', ' ', CookieConsent::getRemoveInvalidCharacters(str_replace("\'", "'", $key)));
                $actualKey = CookieConsent::getRemoveInvalidCharacters($key);

                // Add key if it doesn't exist
                if (!array_key_exists($actualKey, $translations)) {
                    $translations[$actualKey] = $default ?? $processedKey;

                    $content = "<?php\n\nreturn [\n";
                    foreach ($translations as $k => $v) {
                        $content .= "    '{$k}' => '{$v}',\n";
                    }
                    $content .= "];\n";

                    File::put($filePath, $content);
                } elseif (array_key_exists($actualKey, $translations)) {
                    return $translations[$actualKey] ?? $default;
                } else {
                    return __('messages.' . $actualKey);
                }
            }
        } catch (\Exception $exception) {

        }
        return $key;
    }
}
