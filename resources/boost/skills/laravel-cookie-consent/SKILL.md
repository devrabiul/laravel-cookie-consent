---
name: laravel-cookie-consent
description: Set up, configure and use the devrabiul/laravel-cookie-consent package (GDPR / UK ICO cookie banner for Laravel). Use when adding a cookie consent banner, gating analytics, marketing or tracking scripts (Google Analytics, GTM, Facebook Pixel) behind consent, checking consent in PHP with CookieConsent::hasConsent() or the @cookieConsent Blade directive, enabling Google Consent Mode v2, changing cookie categories, consent versions, lifetimes, themes, colors, layouts, policy links or the floating cookie settings button, or making the banner work with Livewire, Turbo or Inertia.
---

# Laravel Cookie Consent (devrabiul/laravel-cookie-consent)

GDPR / UK ICO cookie consent banner for Laravel 8–13 (PHP 8.0+). No frontend dependencies.
Facade: `Devrabiul\CookieConsent\Facades\CookieConsent` (auto-aliased as `CookieConsent`).
Config file: `config/laravel-cookie-consent.php` (not `cookie-consent.php`).

## Install

```bash
composer require devrabiul/laravel-cookie-consent
php artisan vendor:publish --provider="Devrabiul\CookieConsent\CookieConsentServiceProvider"
```

The service provider and facade are auto-discovered. Publishing the config is optional; without it the
package defaults are used.

## Add the banner to the layout

Put both calls in the **root Blade layout** used by every page:

```blade
<head>
    {!! CookieConsent::styles() !!}   {{-- must come BEFORE any Google tag / GTM snippet --}}
</head>
<body>
    ...
    {!! CookieConsent::scripts() !!}  {{-- right before </body> --}}
</body>
```

- Always use `{!! !!}`, never `{{ }}` – the methods return HTML.
- `styles()` also outputs the Google Consent Mode defaults when that feature is enabled, so it must stay in
  `<head>` above Google tags. If styles must load elsewhere, put `{!! CookieConsent::googleConsentMode() !!}` at
  the top of `<head>` instead (output once per request, so repeating it is safe).
- `scripts(options: [...])` overrides config values for one page. Do not override `cookie_prefix`,
  `consent_version`, `cookie_lifetime`, `reject_lifetime` or `cookie_categories` there – the server-side checks read
  the config file only, so the banner and `hasConsent()` would disagree. Set those in the config.

## Load third-party scripts only after consent

Never put tracking scripts directly in the layout. Use one of these, in order of preference:

1. **Google tags with Consent Mode v2** (GA4, Google Ads, GTM): enable `google_consent_mode.enabled` and load the
   Google tag normally below `styles()`. Google tags respect the consent state; no `js_action` needed.
2. **Category `js_action`** – a global JavaScript function name called when the visitor grants that category
   (runs at most once per page load):

   ```php
   // config/laravel-cookie-consent.php
   'analytics' => ['enabled' => true, 'locked' => false, 'js_action' => 'loadGoogleAnalytics', 'title' => '...', 'description' => '...'],
   ```

   ```blade
   <script>
       function loadGoogleAnalytics() { /* inject the vendor script here */ }
   </script>
   ```

   Define the function on `window` (a plain top-level `function` in a non-module `<script>`), not inside a
   Vite/ES module, or the banner cannot find it.
3. **Server-side, for markup rendered on the next request** – the Blade directive:

   ```blade
   @cookieConsent('analytics')
       <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXX"></script>
   @elsecookieConsent('marketing')
       {{-- marketing only --}}
   @else
       {{-- no consent --}}
   @endcookieConsent
   ```

4. **JavaScript events** on `document`: `cookieConsentAccepted`, `cookieConsentRejected`,
   `cookiePreferencesSaved` (`event.detail` = `{ category: bool }`).

## Check consent in PHP

```php
use Devrabiul\CookieConsent\Facades\CookieConsent;

CookieConsent::hasConsent('analytics');           // bool, current request
CookieConsent::hasConsent('marketing', $request); // specific request
CookieConsent::hasValidConsent();                 // visitor made a current decision?
CookieConsent::consentRecord();                   // ['version','status','timestamp','categories'] | null
CookieConsent::googleConsentState();              // ['analytics_storage' => 'granted'|'denied', ...]
```

`hasConsent()` is true only if the category exists in `cookie_categories` **and** is `enabled`, the consent cookie
is valid, matches `consent_version` and is not expired, **and** the category is `locked` or was accepted. After
"Reject all" only locked categories are true. Category keys are the array keys (`necessary`, `analytics`,
`marketing`, `preferences`, or custom ones you add).

Rules that follow from this:

- A category that is `enabled => false` is not shown in the banner **and** `hasConsent()` returns false for it.
  The defaults for `analytics`, `marketing` and `preferences` read `COOKIE_CONSENT_ANALYTICS`,
  `COOKIE_CONSENT_MARKETING` and `COOKIE_CONSENT_PREFERENCES`, which default to `false` – enable the ones the
  site uses.
- Consent is written by JavaScript; a page rendered before the click does not change until the next request.
- Do not page-cache / CDN-cache HTML containing consent-dependent output (or vary the cache by the consent cookies).
- The consent cookies are unencrypted and readable even with `EncryptCookies`; `$request->cookie()` returns null for
  them – always use the facade methods, never read the cookies yourself.

## Key configuration (`config/laravel-cookie-consent.php`)

| Key | Notes |
| --- | --- |
| `enabled` | `COOKIE_CONSENT_ENABLED`. When false, `scripts()` outputs nothing. |
| `cookie_prefix` | Defaults to `APP_NAME`. Cookies: `{slug}_consent`, `{slug}_consent_preferences`. Changing it discards all consent. |
| `consent_version` | String, compared exactly. Bump **only** when cookie usage materially changes (new category, new third-party service, policy change) – visitors are asked again. Not the package version; not for cosmetic edits. Use a string (`'2.0'`), not a float. |
| `cookie_lifetime` / `reject_lifetime` | Days after accept / reject (defaults 365 / 7). |
| `cookie_categories` | `enabled`, `locked`, optional `js_action`, `title`, `description`. Keep `necessary` locked. |
| `consent_modal_layout` | `box`, `box-inline`, `box-wide`, `cloud`, `cloud-inline`, `bar`, `bar-inline`. |
| `preferences_modal_enabled` / `preferences_modal_layout` | Layout `bar` or `box`. |
| `disable_page_interaction` | Blocks the page until the visitor decides. |
| `theme` / `theme_preset` | Presets: `basic`, `modern-blue`, `trust-green`, `soft-neutral`, `dark`. |
| `card_bg_color` / `card_text_color` | Hex, rgb(a), hsl(a) or named color; invalid values are ignored. Set both together for contrast. |
| `floating_button` | `enabled`, `position` (`bottom-left`/`bottom-right`), `label`. Lets visitors reopen preferences. |
| `google_consent_mode` | `enabled`, `wait_for_update`, `ads_data_redaction`, `url_passthrough`, `consent_types` (Google type => category). |
| `policy_links` | `[['text' => ..., 'link' => ...]]`; relative paths go through `url()`. Empty text/link entries are hidden. |
| `asset_url` | Custom base URL for package assets; leave null on localhost. |

Texts (`cookie_title`, `cookie_description`, `cookie_*_btn_text`, `cookie_modal_title`, `cookie_modal_intro`) are
plain config strings.

Relevant `.env` keys: `COOKIE_CONSENT_ENABLED`, `COOKIE_CONSENT_VERSION`, `COOKIE_CONSENT_LIFETIME`,
`COOKIE_REJECT_LIFETIME`, `COOKIE_CONSENT_ANALYTICS`, `COOKIE_CONSENT_MARKETING`, `COOKIE_CONSENT_PREFERENCES`,
`COOKIE_CONSENT_PREFERENCES_ENABLED`, `COOKIE_CONSENT_MODAL_LAYOUT`, `COOKIE_CONSENT_THEME_PRESET`,
`COOKIE_CONSENT_CARD_BG_COLOR`, `COOKIE_CONSENT_CARD_TEXT_COLOR`, `COOKIE_CONSENT_FLOATING_BUTTON`,
`COOKIE_CONSENT_FLOATING_BUTTON_POSITION`, `COOKIE_CONSENT_GOOGLE_CONSENT_MODE`, `COOKIE_CONSENT_PRIVACY_POLICY_URL`,
`COOKIE_CONSENT_TERMS_URL`, `COOKIE_CONSENT_ASSET_URL`.

After changing config in production, run `php artisan config:clear` (or `config:cache`).

## Let visitors change or withdraw consent (required by GDPR Art. 7(3) / UK ICO)

Enable `floating_button.enabled`, and/or add a link, e.g. in the footer:

```html
<a href="#" class="showHideToggleCookiePreferencesModal">Cookie settings</a>
<!-- or: onclick="showHideToggleCookiePreferencesModal()" -->
```

## Google Consent Mode v2

1. Set `COOKIE_CONSENT_GOOGLE_CONSENT_MODE=true` and enable the categories used in `consent_types`.
2. Keep `{!! CookieConsent::styles() !!}` above the gtag.js / GTM snippet in `<head>`.
3. Every type starts `denied` (types mapped to a locked category start `granted`); stored consent is applied before
   tags run; each decision sends `gtag('consent', 'update', …)` and pushes a `cookie_consent_update` event to
   `dataLayer` (use it as a GTM Custom Event trigger).

## SPA (Livewire `wire:navigate`, Turbo, Inertia)

Works automatically – the banner re-initialises on `livewire:navigated`, `turbo:load` and `inertia:navigate`.
For other client-side routers call `window.initCookieConsent()` after each navigation. Keep `scripts()` in the
root Blade layout.

## JavaScript globals

`window.getCookie(name)`, `window.getCookiePreferences()` (null when consent is missing, outdated or expired),
`window.showHideToggleCookiePreferencesModal()`, `window.initCookieConsent()`.

## Common mistakes to avoid

- Hard-coding tracking scripts in the layout without `@cookieConsent`, `js_action` or Consent Mode.
- Placing `styles()` below the Google tag when Consent Mode is enabled.
- Gating on a category that is disabled in config (always false).
- Bumping `consent_version` on package upgrades or text/color changes.
- Overriding consent-related options in `scripts(options: …)` instead of the config file.
- Editing files in `vendor/`; publish the config and change it instead.

Full documentation: https://github.com/devrabiul/laravel-cookie-consent
