# Laravel Cookie Consent

A GDPR-compliant solution offering enterprise-grade compliance with fully customizable cookie banners for Laravel
applications. Simplifies regulatory requirements while maintaining excellent user experience and complete customization
capabilities.

[![Latest Stable Version](https://poser.pugx.org/devrabiul/laravel-cookie-consent/v/stable)](https://packagist.org/packages/devrabiul/laravel-cookie-consent)
[![Total Downloads](https://poser.pugx.org/devrabiul/laravel-cookie-consent/downloads)](https://packagist.org/packages/devrabiul/laravel-cookie-consent)
[![Monthly Downloads](https://poser.pugx.org/devrabiul/laravel-cookie-consent/d/monthly)](https://packagist.org/packages/devrabiul/laravel-cookie-consent)
![GitHub license](https://img.shields.io/github/license/devrabiul/laravel-cookie-consent)
[![Buy us a tree](https://img.shields.io/badge/Treeware-%F0%9F%8C%B3-lightgreen)](https://plant.treeware.earth/devrabiul/laravel-cookie-consent)
![GitHub Repo stars](https://img.shields.io/github/stars/devrabiul/laravel-cookie-consent?style=social)

## Features

- 🔥 **One-Click Implementation** – Simple installation via Composer with auto-loaded assets
- ⚡ **Zero Performance Impact** – Lightweight with lazy-loaded components
- 🌍 **RTL & i18n Support** – Full right-to-left compatibility + multilingual translations
- 🌙 **Dark Mode Support** – Auto dark/light mode matching system preferences
- 🛡 **Granular Consent Control** – Category-level cookie management (necessary/analytics/marketing)
- 📦 **Complete Customization** – Override every color, text, and layout via config
- 📱 **Responsive Design** – Perfectly adapts to all devices (mobile/tablet/desktop)
- 🧩 **No Frontend Dependencies** – No jQuery, Bootstrap, or Tailwind required — works everywhere effortlessly
- 🔐 **Server-side Consent Checks** – `CookieConsent::hasConsent('analytics')` and the `@cookieConsent` Blade directive
- 🔄 **Consent Versioning** – Ask visitors again when your cookie policy changes
- 📈 **Google Consent Mode v2** – Works with gtag.js, Google Tag Manager, GA4 and Google Ads
- 🍪 **Floating Cookie Settings Button** – Lets visitors change or withdraw consent at any time
- 🔁 **SPA Support** – Livewire `wire:navigate`, Turbo / Hotwire and Inertia

---

## 🚀 Live Demo

👉 [Try the Live Demo](https://rixetbd.com/docs?package=laravel-cookie-consent)

![Live Demo Thumbnail](https://rixetbd.com/storage/app/public/package/devrabiul/laravel-cookie-consent.webp)

---

## Requirements

- PHP 8.0 or higher
- Laravel 8.x – 13.x

## Installation

To get started with Cookie Consent, follow these simple steps:

1. Install the package via Composer:

```bash
composer require devrabiul/laravel-cookie-consent
```

2. Publish the package resources by running: (Normal publish)

```bash
php artisan vendor:publish --provider="Devrabiul\CookieConsent\CookieConsentServiceProvider"
```

## Basic Usage

Include these components in your Blade templates:

1. Add styles in the `<head>` section:

```php
{!! CookieConsent::styles() !!}
```

2. Add scripts before closing `</body>`:

```php
{!! CookieConsent::scripts() !!}
```

### Complete Example

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Page</title>
    {!! CookieConsent::styles() !!}
</head>
<body>

    <!-- Your content -->
    {!! CookieConsent::scripts() !!}
</body>
</html>
```

## Advanced Configuration

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Page</title>
    {!! CookieConsent::styles() !!}
</head>
<body>

    <!-- Your content -->
    {!! CookieConsent::scripts(options: [
        'cookie_lifetime' => config('laravel-cookie-consent.cookie_lifetime', 7),
        'reject_lifetime' => config('laravel-cookie-consent.reject_lifetime', 1),
        'disable_page_interaction' => config('laravel-cookie-consent.disable_page_interaction', true),
        'preferences_modal_enabled' => config('laravel-cookie-consent.preferences_modal_enabled', true),
        'consent_modal_layout' => config('laravel-cookie-consent.consent_modal_layout', 'bar-inline'),
        'flip_button' => config('laravel-cookie-consent.flip_button', true),
        'theme' => config('laravel-cookie-consent.theme', 'default'),
        'cookie_prefix' => config('laravel-cookie-consent.cookie_prefix', 'Laravel_App'),
        'policy_links' => config('laravel-cookie-consent.policy_links', [
            ['text' => CookieConsent::translate('Privacy Policy'), 'link' => url('privacy-policy')],
            ['text' => CookieConsent::translate('Terms & Conditions'), 'link' => url('terms-and-conditions')],
        ]),
        'cookie_categories' => config('laravel-cookie-consent.cookie_categories', [
            'necessary' => [
                'enabled' => true,
                'locked' => true,
                'js_action' => 'loadGoogleAnalytics',
                'title' => CookieConsent::translate('Essential Cookies'),
                'description' => CookieConsent::translate('These cookies are essential for the website to function properly.'),
            ],
            'analytics' => [
                'enabled' => env('COOKIE_CONSENT_ANALYTICS', false),
                'locked' => false,
                'title' => CookieConsent::translate('Analytics Cookies'),
                'description' => CookieConsent::translate('These cookies help us understand how visitors interact with our website.'),
            ],
            'marketing' => [
                'enabled' => env('COOKIE_CONSENT_MARKETING', false),
                'locked' => false,
                'js_action' => 'loadFacebookPixel',
                'title' => CookieConsent::translate('Marketing Cookies'),
                'description' => CookieConsent::translate('These cookies are used for advertising and tracking purposes.'),
            ],
            'preferences' => [
                'enabled' => env('COOKIE_CONSENT_PREFERENCES', false),
                'locked' => false,
                'js_action' => 'loadPreferencesFunc',
                'title' => CookieConsent::translate('Preferences Cookies'),
                'description' => CookieConsent::translate('These cookies allow the website to remember user preferences.'),
            ],
        ]),
        'cookie_title' => CookieConsent::translate('Cookie Disclaimer'),
        'cookie_description' => CookieConsent::translate('This website uses cookies to enhance your browsing experience, analyze site traffic, and personalize content. By continuing to use this site, you consent to our use of cookies.'),
        'cookie_modal_title' => CookieConsent::translate('Cookie Preferences'),
        'cookie_modal_intro' => CookieConsent::translate('You can customize your cookie preferences below.'),
        'cookie_accept_btn_text' => CookieConsent::translate('Accept All'),
        'cookie_reject_btn_text' => CookieConsent::translate('Reject All'),
        'cookie_preferences_btn_text' => CookieConsent::translate('Manage Preferences'),
        'cookie_preferences_save_text' => CookieConsent::translate('Save Preferences'),
    ]) !!}

</body>
</html>
```

### 🌙 Enable Dark Mode

Add `theme="dark"` to your `<body>` tag to automatically enable dark mode.

```html
<body theme="dark"></body>
```

---

## Theme Preset Support

This package now supports **preset color themes** for cookie banners. You can choose from:

| Preset         | Description                                                    |
| -------------- | -------------------------------------------------------------- |
| `basic`        | Default neutral theme, clean and simple.                       |
| `modern-blue`  | Professional blue style, ideal for SaaS/web apps.              |
| `trust-green`  | Privacy-friendly green, promotes trust and eco-friendly vibes. |
| `soft-neutral` | Minimal soft gray, subtle and non-intrusive.                   |
| `dark`         | Dark mode theme, low-light friendly.                           |

### How to configure

In `config/cookie-consent.php`:

```php
/*
|--------------------------------------------------------------------------
| Theme Preset
|--------------------------------------------------------------------------
| basic        - Default neutral theme
| modern-blue  - Professional blue style
| trust-green  - Privacy-friendly green style
| soft-neutral - Minimal soft gray theme
| dark         - Dark theme
*/
'theme_preset' => env('COOKIE_CONSENT_THEME_PRESET', 'basic'),
```

---

### Card Colors

Override the background and text color of the consent banner and the preferences modal. They take precedence over the
theme and theme preset; with a custom background, the policy links strip at the bottom of the banner follows it too.

```php
// config/laravel-cookie-consent.php
'card_bg_color' => env('COOKIE_CONSENT_CARD_BG_COLOR', null),     // e.g. '#0f172a'
'card_text_color' => env('COOKIE_CONSENT_CARD_TEXT_COLOR', null), // e.g. '#f8fafc'
```

Both accept hex (`#fff`, `#f8fafc`, `#f8fafccc`), `rgb()`/`rgba()`, `hsl()`/`hsla()` or a named color. Invalid values
are ignored and the theme's color is used. Set both together so the text keeps enough contrast with the background.
They can also be passed per page: `CookieConsent::scripts(options: ['card_bg_color' => '#0f172a', 'card_text_color' => '#f8fafc'])`.

---

### 🌐 Enable RTL Mode

Add `dir="rtl"` to your `<body>` tag to enable right-to-left layout for RTL languages.

```html
<body dir="rtl"></body>
```

## Layout Options

### Config Status Control

```bash
COOKIE_CONSENT_ENABLED=true
COOKIE_CONSENT_PREFERENCES_ENABLED=true
COOKIE_CONSENT_ASSET_URL=https://your-domain.com // if you are in localhost, avoid this or put null

COOKIE_CONSENT_ANALYTICS=true
COOKIE_CONSENT_MARKETING=true
COOKIE_CONSENT_PREFERENCES=true

COOKIE_CONSENT_VERSION=1.0
COOKIE_CONSENT_CARD_BG_COLOR=#0f172a
COOKIE_CONSENT_CARD_TEXT_COLOR=#f8fafc
COOKIE_CONSENT_FLOATING_BUTTON=true
COOKIE_CONSENT_FLOATING_BUTTON_POSITION=bottom-left
COOKIE_CONSENT_GOOGLE_CONSENT_MODE=true
COOKIE_CONSENT_LIFETIME=365
COOKIE_REJECT_LIFETIME=7
COOKIE_CONSENT_PRIVACY_POLICY_URL=https://your-domain.com/privacy-policy
COOKIE_CONSENT_TERMS_URL=https://your-domain.com/terms-and-conditions
```

### Consent Modal Styles

- **`box`** - Compact floating dialog
- **`box-inline`** - Inline positioned box
- **`box-wide`** - Expanded floating dialog
- **`cloud`** - Modern floating design
- **`cloud-inline`** - Compact cloud variant
- **`bar`** - Top/bottom banner
- **`bar-inline`** - Compact banner

*Default: `box-wide`*

### Preferences Modal Styles

- **`bar`** - Full-width layout
- **`box`** - Centered popup

*Default: `bar`*

## Configuration

Edit `config/cookie-consent.php` to modify:

- Cookie lifetimes
- Custom asset URL
- Visual styles
- Text content
- Category settings

### Example service loader (replace with your actual implementation)

```javascript
function loadGoogleAnalytics() {
    // Please put your GA script in loadGoogleAnalytics()
    // You can define function name from - {!! CookieConsent::scripts() !!}

    window.dataLayer = window.dataLayer || [];

    function gtag() {
        dataLayer.push(arguments);
    }

    gtag('js', new Date());
    gtag('config', 'YOUR_GA_ID');

    // Load the GA script
    const script = document.createElement('script');
    script.src = 'https://www.googletagmanager.com/gtag/js?id=YOUR_GA_ID';
    script.async = true;
    document.head.appendChild(script);
}

function loadFacebookPixel() {
    // Please put your marketing script in loadFacebookPixel()
    // You can define function name from - {!! CookieConsent::scripts() !!}

    !function (f, b, e, v, n, t, s) {
        if (f.fbq) return;
        n = f.fbq = function () {
            n.callMethod ?
                n.callMethod.apply(n, arguments) : n.queue.push(arguments)
        };
        if (!f._fbq) f._fbq = n;
        n.push = n;
        n.loaded = !0;
        n.version = '2.0';
        n.queue = [];
        t = b.createElement(e);
        t.async = !0;
        t.src = v;
        s = b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t, s)
    }(window, document, 'script',
        'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', 'YOUR_PIXEL_ID');
    fbq('track', 'PageView');
}
```

---

## Cookie Lifetime

Both consent cookies (the status cookie and the preferences cookie) expire together, based on the visitor's decision:

```php
// config/laravel-cookie-consent.php
'cookie_lifetime' => env('COOKIE_CONSENT_LIFETIME', 365), // days, after "Accept all" or "Save preferences"
'reject_lifetime' => env('COOKIE_REJECT_LIFETIME', 7),    // days, after "Reject all"
```

Values must be positive numbers of days (fractions such as `0.5` are allowed); invalid values fall back to `365` and `7`.
The same lifetimes are enforced by the browser and by the server-side checks below, so lowering a lifetime also
shortens consent that visitors have already given.

---

## Policy Links

Policy links can be absolute URLs or paths relative to your application. Paths are resolved with `url()` when the
banner is rendered, so the fallback also works with `php artisan config:cache`:

```php
'policy_links' => [
    ['text' => 'Privacy Policy', 'link' => env('COOKIE_CONSENT_PRIVACY_POLICY_URL') ?: 'privacy-policy'],
    ['text' => 'Terms and Conditions', 'link' => env('COOKIE_CONSENT_TERMS_URL') ?: 'terms-and-conditions'],
],
```

Links with an empty `text` or `link` are not displayed.

---

## Consent Versioning

Each visitor's consent is stored together with the consent version it was given for:

```php
// config/laravel-cookie-consent.php
'consent_version' => env('COOKIE_CONSENT_VERSION', '1.0'),
```

When you change `consent_version`, all existing consent becomes outdated: the banner is shown again and
`CookieConsent::hasConsent()` returns `false` until the visitor decides again.

**When to increase the version** – whenever the consent you asked for no longer covers what you do, for example:

- you add a cookie category or a new third-party service (analytics, ads, chat widgets, embeds…)
- an existing category now sets cookies for a new purpose
- your cookie or privacy policy changes in a way visitors should agree to again

Do **not** increase it for cosmetic changes (texts, colors, layout) – visitors would be asked again for no reason.
Any string works (`'1.1'`, `'2'`, `'2026-10'`); it is compared exactly. Always use a string – a float such as `2.0`
in PHP becomes `'2'`.

The cookie names do not change with the version or the year: they are `{prefix}_consent` and
`{prefix}_consent_preferences`, where `{prefix}` is `Str::slug(cookie_prefix)`. Changing `cookie_prefix` discards
all existing consent.

---

## Server-side Consent Checks

Check consent in PHP by the category keys of `cookie_categories`:

```php
use Devrabiul\CookieConsent\Facades\CookieConsent;

if (CookieConsent::hasConsent('analytics')) {
    // e.g. pass the analytics ID to the view
}

CookieConsent::hasConsent('marketing', $request); // check a specific request
CookieConsent::hasValidConsent();                 // has the visitor made a current decision?
CookieConsent::consentRecord();                   // ['version', 'status', 'timestamp', 'categories'] or null
```

`hasConsent()` returns `true` only when:

- the category exists in `cookie_categories` and is `enabled`, and
- the visitor's consent cookie is well-formed, matches the current `consent_version` and is within its lifetime, and
- the category is `locked` (e.g. `necessary`, granted with any current decision), or the visitor accepted it.

It returns `false` for missing, malformed, tampered, outdated or expired cookies, for unknown or disabled categories,
and for every non-locked category after "Reject all".

### Blade directive

```blade
@cookieConsent('analytics')
    <script async src="https://www.googletagmanager.com/gtag/js?id=YOUR_GA_ID"></script>
@elsecookieConsent('marketing')
    {{-- only marketing consent --}}
@else
    {{-- no consent for either --}}
@endcookieConsent
```

The condition is evaluated on every request (not when the view is compiled), so it is safe with `view:cache`.

### Limitations

- The directive only controls **server-rendered markup in that template**. It does not block scripts added
  elsewhere (layouts, tag managers, packages, browser extensions) or requests those scripts make – load those through
  the directive, the category `js_action` functions, or the `cookieConsentAccepted` / `cookiePreferencesSaved`
  JavaScript events.
- Consent is saved in the browser. A page rendered **before** the visitor clicks a button does not change until the
  next request; use `js_action` if a service must start immediately after consent.
- Do not cache full HTML responses that contain consent-dependent output (page caches, CDN caching), or vary the
  cache by the consent cookies.
- The server reads the package configuration. If you override `cookie_prefix`, `consent_version`, `cookie_lifetime`,
  `reject_lifetime` or `cookie_categories` through `CookieConsent::scripts(options: [...])`, set the same values in
  the config file, otherwise the banner and `hasConsent()` can disagree.
- The consent cookies are written by JavaScript and are not encrypted. They are read even when Laravel's
  `EncryptCookies` middleware is active; `$request->cookie()` itself still returns `null` for them.

---

## Google Consent Mode v2

Google requires [Consent Mode v2](https://developers.google.com/tag-platform/security/guides/consent) for Google Ads
and Analytics in the EEA and UK. Enable it in the config:

```php
// config/laravel-cookie-consent.php
'google_consent_mode' => [
    'enabled' => env('COOKIE_CONSENT_GOOGLE_CONSENT_MODE', false),
    'wait_for_update' => 500,       // ms Google tags wait for the visitor's consent
    'ads_data_redaction' => true,   // redact ad click IDs while ad_storage is denied
    'url_passthrough' => false,     // pass ad click info through URLs while ad_storage is denied
    'consent_types' => [            // Google consent type => cookie category
        'ad_storage' => 'marketing',
        'ad_user_data' => 'marketing',
        'ad_personalization' => 'marketing',
        'analytics_storage' => 'analytics',
        'functionality_storage' => 'preferences',
        'personalization_storage' => 'preferences',
        'security_storage' => 'necessary',
    ],
],
```

Then make sure `{!! CookieConsent::styles() !!}` comes **before** your Google tag or Google Tag Manager snippet in
`<head>` – it outputs the consent defaults, which must run before Google tags load:

```blade
<head>
    {!! CookieConsent::styles() !!}  {{-- consent defaults first --}}

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-XXXXXXX');
    </script>
</head>
```

If your styles must be loaded elsewhere, put `{!! CookieConsent::googleConsentMode() !!}` at the top of `<head>`
instead. It is output only once per request, so `styles()` will not repeat it.

How it works:

1. Every consent type starts as `denied`; types mapped to a locked category (`security_storage` → `necessary`) start
   as `granted`.
2. For returning visitors, the stored consent is applied immediately in `<head>`, before any tag runs.
3. When a visitor accepts, rejects or saves preferences, the banner sends `gtag('consent', 'update', …)` and pushes a
   `cookie_consent_update` event to the `dataLayer`. In Google Tag Manager, use it as a *Custom Event* trigger.

With Consent Mode, Google tags load straight away and respect the consent state, so you do not need a `js_action`
to load them. Use `js_action` for non-Google services.

On the server, `CookieConsent::googleConsentState()` returns the same state (e.g. `['analytics_storage' => 'granted', …]`),
for example for server-side tagging or the GA4 Measurement Protocol.

---

## Floating Cookie Settings Button

A small button fixed to a corner of the page that reopens the cookie preferences, so visitors can change or withdraw
their consent at any time – as easily as they gave it (GDPR Art. 7(3), UK ICO).

```php
// config/laravel-cookie-consent.php
'floating_button' => [
    'enabled' => env('COOKIE_CONSENT_FLOATING_BUTTON', false),
    'position' => env('COOKIE_CONSENT_FLOATING_BUTTON_POSITION', 'bottom-left'), // bottom-left | bottom-right
    'label' => 'Cookie settings', // accessible name and tooltip
],
```

The button appears only after the visitor has made a choice (while the banner is visible it is hidden), uses the
[card colors](#card-colors), and is hidden when printing.

---

## SPA Support (Livewire, Turbo, Inertia)

The banner works with client-side navigation out of the box:

- **Livewire** `wire:navigate` – re-initialises on `livewire:navigated`
- **Turbo / Hotwire** – re-initialises on `turbo:load`, and closes the preferences modal before Turbo caches a page
- **Inertia** – re-initialises on `inertia:navigate` (the banner lives in your root Blade layout, so it stays on the
  page)

Re-initialising is safe to repeat: clicks are handled by a single delegated listener, the script can be re-executed
without registering anything twice, and each category's `js_action` runs at most once per page load, so services such
as analytics are not loaded again on every navigation.

For other client-side routers, call `window.initCookieConsent()` after each page change.

> The server-side checks (`hasConsent()`, `@cookieConsent`) run when a page is rendered on the server. Pages fetched
> by Livewire or Inertia requests are evaluated with the consent cookies of that request, but content already on the
> page does not change when the visitor makes a choice – use the JavaScript events or `js_action` for that.

---

## Change Cookie Preferences Link

To comply with UK ICO and GDPR regulations, users **must be able to revisit and update their cookie preferences at any time**. This package now supports that functionality out of the box.

You can add a link anywhere on your site to let users open the cookie preferences modal again and update their choices:

```html
<a onclick="showHideToggleCookiePreferencesModal()">Change Cookie Preferences</a>
```

Or use the class-based approach (useful if you want to attach event handlers via JavaScript or multiple links):

```html
<a class="showHideToggleCookiePreferencesModal">Change Cookie Preferences</a>
```

### How It Works

* Clicking the link will **show the cookie preferences modal**, allowing the user to change their settings.
* This feature **resets the consent banner/modal visibility** so users can modify their choices in compliance with the ICO’s requirement.
* The modal respects existing preferences but allows full customization and saving new preferences.

---

## Compliance with UK ICO and GDPR

This package is designed to help your Laravel application meet the requirements of the UK Information Commissioner’s Office (ICO) and the GDPR by:

* **Providing explicit, granular cookie consent** with customizable categories (e.g., necessary, analytics, marketing, preferences).
* **Allowing users to easily revisit and change their cookie preferences** at any time using the “Change Cookie Preferences” link.
* Supporting **clear cookie banners and modals** with translations and accessibility considerations.
* Enabling **consent logging and event dispatching** for auditing and analytics purposes.

For more details, please review the UK ICO guidance on cookies:
[ICO Guide to Cookies and Similar Technologies](https://ico.org.uk/for-organisations/guide-to-pecr/cookies-and-similar-technologies/)

---

### 🎯 Get Started Today!

Experience the magic of CookieConsent and enhance your Laravel applications with Cookie Consent.

🔗 **GitHub:** [Laravel Cookie Consent](https://github.com/devrabiul/laravel-cookie-consent)  
🔗 **Packagist:
** [https://packagist.org/packages/devrabiul/laravel-cookie-consent](https://packagist.org/packages/devrabiul/laravel-cookie-consent)

## Upgrading to v2.2

- **Cookie names no longer contain the year.** Consent is now stored in `{prefix}_consent` and
  `{prefix}_consent_preferences` instead of `{prefix}_{year}` and `{prefix}_{year}_preferences`. Previously every
  visitor was asked again on January 1st; this no longer happens – use `consent_version` instead.
- **Existing consent is kept.** Consent saved by earlier versions in the current year's cookies counts as consent
  version `1.0`. While `consent_version` is `1.0` (the default), it is converted to the new cookies on the visitor's
  next page view and honoured by `hasConsent()`. If you set a different version, visitors are asked again.
- **Published config files:** add `consent_version`, and replace the policy links' `env(..., '') ?? url(...)`
  with `env(...) ?: 'privacy-policy'` (see [Policy Links](#policy-links)). With the old lines the links have an
  empty URL and are no longer displayed. A missing `consent_version` is treated as `1.0`.
- **The preferences cookie now expires with the decision** (`cookie_lifetime` / `reject_lifetime`) instead of a
  fixed 365 days.
- **The `/laravel-cookie-consent/script-utils` route was removed.** It only returned an empty `window.onload`
  handler, cost one extra request per page and could overwrite your own `window.onload`.
- **`getCookiePreferences()` (JavaScript)** now returns `null` when consent is missing, outdated or expired, and
  only includes the categories shown in the preferences modal.
- **Each `js_action` now runs at most once per page load.** Before, saving preferences again called it again
  (e.g. loading the analytics script twice).
- **The banner script initialises itself** on `DOMContentLoaded` or immediately when loaded later, and after SPA
  navigation. Clicks are handled through event delegation; `window.getCookie()`, `window.getCookiePreferences()`
  and `window.showHideToggleCookiePreferencesModal()` are unchanged.
- **New, disabled by default:** Google Consent Mode v2 and the floating settings button.

---

## Testing

```bash
composer install
composer test      # Pest (PHP)
composer test:js   # Node 18+ (script.js, no npm dependencies)
```

---

## Contributing

We welcome contributions to CookieConsent! If you would like to contribute, please fork the repository and submit a pull
request. For any issues or feature requests, please open an issue on GitHub.

Please:

1. Fork the repository
2. Create your feature branch
3. Submit a pull request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 🌱 Treeware

This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/devrabiul/laravel-cookie-consent) to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.

## Contact

For support or inquiries, please reach out to us at [Send Mail](mailto:devrabiul@gmail.com).
