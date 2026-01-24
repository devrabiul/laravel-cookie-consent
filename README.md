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

---

## 🚀 Live Demo

👉 [Try the Live Demo](https://rixetbd.com/devrabiul/laravel-cookie-consent?vendor=devrabiul)

![Live Demo Thumbnail](https://rixetbd.com/storage/app/public/package/devrabiul/laravel-cookie-consent.webp)

---

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
<body theme="dark">
```

---

### 🌐 Enable RTL Mode

Add `dir="rtl"` to your `<body>` tag to enable right-to-left layout for RTL languages.

```html
<body dir="rtl">
```

## Layout Options

### Config Status Control

```bash
COOKIE_CONSENT_ENABLED=true
COOKIE_CONSENT_PREFERENCES_ENABLED=true

COOKIE_CONSENT_ANALYTICS=true
COOKIE_CONSENT_MARKETING=true
COOKIE_CONSENT_PREFERENCES=true
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
