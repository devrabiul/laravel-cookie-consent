<?php

/**
 * Cookie Consent Configuration
 *
 * This file contains all the configuration options for the cookie consent system.
 * It allows customization of the cookie banner appearance, behavior, and compliance settings.
 *
 * @package Config
 * @author Muhammad Rabiul
 * @license MIT
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cookie Consent Prefix
    |--------------------------------------------------------------------------
    | Used to name the consent cookies: "{slug}_consent" (status) and
    | "{slug}_consent_preferences" (versioned consent record), where {slug} is
    | Str::slug() of this value. Changing it discards all existing consent.
    | Defaults to APP_NAME.
    */
    'cookie_prefix' => env('APP_NAME', 'Laravel_App'),

    /*
    |--------------------------------------------------------------------------
    | Consent Version
    |--------------------------------------------------------------------------
    | Identifies the version of your cookie policy that visitors consent to.
    | It is stored with each visitor's consent; when you change this value,
    | existing consent is treated as outdated and the banner is shown again.
    |
    | This is NOT the package version. Keep it unchanged when you update the
    | package; otherwise every visitor is asked for consent again.
    |
    | Increase it when your cookie usage materially changes, e.g. you add a
    | category, start using a new third-party service, or change your cookie
    | policy. Do not change it for cosmetic edits (texts, colors, layout).
    |
    | Consent given before consent versioning existed counts as version "1.0".
    */
    'consent_version' => env('COOKIE_CONSENT_VERSION', '1.0'),

    /**
     * Enable or disable the cookie consent banner
     *
     * @default true
     * @env COOKIE_CONSENT_ENABLED
     */
    'enabled' => env('COOKIE_CONSENT_ENABLED', true),


    /*
    |--------------------------------------------------------------------------
    | Custom Asset URL
    |--------------------------------------------------------------------------
    | Optionally define a custom base URL for package assets.
    | If null or empty, Laravel's native asset() helper will be used.
    |
    | Example:
    | https://your-domain.com
    |
    */
    'asset_url' => env('COOKIE_CONSENT_ASSET_URL', null),

    /**
     * Cookie lifetime in days
     *
     * Defines how long an accepted or customised consent (both the status and the
     * preferences cookie) persists in the user's browser. Must be a positive number.
     *
     * @default 365
     * @env COOKIE_CONSENT_LIFETIME
     */
    'cookie_lifetime' => env('COOKIE_CONSENT_LIFETIME', 365),

    /**
     * Rejection cookie lifetime in days
     *
     * Specifies how long the consent cookies persist when users reject cookies.
     * After it expires the banner is shown again. Must be a positive number.
     *
     * @default 7
     * @env COOKIE_REJECT_LIFETIME
     */
    'reject_lifetime' => env('COOKIE_REJECT_LIFETIME', 7),

    /**
     * Consent modal layout style
     *
     * Determines the visual presentation of the consent modal.
     *
     * @default 'bar-inline'
     * @env COOKIE_CONSENT_MODAL_LAYOUT
     * @option box - Small floating box
     * @option box-inline - Small floating box positioned inline
     * @option box-wide - Larger floating box
     * @option cloud - Cloud-like floating consent box
     * @option cloud-inline - Compact cloud-style box
     * @option bar - Simple bar at top or bottom
     * @option bar-inline - Compact inline bar
     */
    'consent_modal_layout' => env('COOKIE_CONSENT_MODAL_LAYOUT', 'bar'),

    /**
     * Enable preferences modal
     *
     * Determines if users can access detailed cookie preferences.
     *
     * @default false
     * @env COOKIE_CONSENT_PREFERENCES_ENABLED
     */
    'preferences_modal_enabled' => env('COOKIE_CONSENT_PREFERENCES_ENABLED', true),

    /**
     * Preferences modal layout style
     *
     * Defines the visual presentation of the preferences modal.
     *
     * @default 'bar'
     * @env COOKIE_CONSENT_PREFERENCES_LAYOUT
     * @option bar - Bar-style modal
     * @option box - Popup-style box
     */
    'preferences_modal_layout' => env('COOKIE_CONSENT_PREFERENCES_LAYOUT', 'bar'),

    /**
     * Enable flip button animation
     *
     * Adds a flip animation effect to consent buttons.
     *
     * @default true
     * @env COOKIE_CONSENT_FLIP_BUTTON
     */
    'flip_button' => env('COOKIE_CONSENT_FLIP_BUTTON', true),

    /**
     * Disable page interaction until consent
     *
     * When enabled, users must interact with the cookie banner before accessing content.
     *
     * @default true
     * @env COOKIE_CONSENT_DISABLE_INTERACTION
     */
    'disable_page_interaction' => env('COOKIE_CONSENT_DISABLE_INTERACTION', true),

    /**
     * Color theme for the cookie banner
     *
     * @default 'default'
     * @env COOKIE_CONSENT_THEME
     * @option default - Standard theme
     * @option dark - Dark mode theme
     * @option light - Light mode theme
     * @option custom - Custom styles (requires additional CSS)
     */
    'theme' => env('COOKIE_CONSENT_THEME', 'default'),

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

    /*
    |--------------------------------------------------------------------------
    | Card Background Color
    |--------------------------------------------------------------------------
    | Background color of the consent banner and the preferences modal.
    | Overrides the color of the theme and theme preset; the policy links strip
    | follows it too. Leave null to use the theme's color.
    |
    | Accepts hex (#fff, #ffffff, #ffffffcc), rgb()/rgba(), hsl()/hsla() or a
    | named color (e.g. "white"). Invalid values are ignored.
    |
    | Example: '#f8fafc'
    */
    'card_bg_color' => env('COOKIE_CONSENT_CARD_BG_COLOR', null),

    /*
    |--------------------------------------------------------------------------
    | Card Text Color
    |--------------------------------------------------------------------------
    | Text color of the consent banner and the preferences modal. Overrides
    | the color of the theme and theme preset. Leave null to use the theme's
    | color. Accepts the same formats as card_bg_color.
    |
    | Set it together with card_bg_color so the text stays readable.
    |
    | Example: '#0f172a'
    */
    'card_text_color' => env('COOKIE_CONSENT_CARD_TEXT_COLOR', null),

    /*
    |--------------------------------------------------------------------------
    | Floating Cookie Settings Button
    |--------------------------------------------------------------------------
    | A small button fixed to a corner of the page that reopens the cookie
    | preferences, so visitors can change or withdraw consent at any time
    | (GDPR Art. 7(3), UK ICO). It is shown only after the visitor has made a
    | choice, and uses the card colors.
    |
    | position - bottom-left | bottom-right
    | label    - accessible name and tooltip of the button
    */
    'floating_button' => [
        'enabled' => env('COOKIE_CONSENT_FLOATING_BUTTON', false),
        'position' => env('COOKIE_CONSENT_FLOATING_BUTTON_POSITION', 'bottom-left'),
        'label' => 'Cookie settings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Consent Mode v2
    |--------------------------------------------------------------------------
    | Sends the visitor's choices to Google tags (gtag.js, Google Tag Manager,
    | Google Analytics 4, Google Ads). The defaults ("denied") are output by
    | {!! CookieConsent::styles() !!}, which must therefore be placed in <head>
    | ABOVE your Google tag / GTM snippet.
    |
    | consent_types      - Google consent type => cookie category it follows.
    |                      Locked categories (e.g. necessary) are always granted.
    | wait_for_update    - Milliseconds Google tags wait for the consent update.
    | ads_data_redaction - Redact ad click identifiers while ad_storage is denied.
    | url_passthrough    - Pass ad click information through URLs while
    |                      ad_storage is denied.
    |
    | A "cookie_consent_update" dataLayer event is pushed on every decision, for
    | use as a Google Tag Manager trigger.
    */
    'google_consent_mode' => [
        'enabled' => env('COOKIE_CONSENT_GOOGLE_CONSENT_MODE', false),
        'wait_for_update' => 500,
        'ads_data_redaction' => true,
        'url_passthrough' => false,
        'consent_types' => [
            'ad_storage' => 'marketing',
            'ad_user_data' => 'marketing',
            'ad_personalization' => 'marketing',
            'analytics_storage' => 'analytics',
            'functionality_storage' => 'preferences',
            'personalization_storage' => 'preferences',
            'security_storage' => 'necessary',
        ],
    ],

    /**
     * Cookie banner title text
     *
     * @default "Cookie Disclaimer"
     */
    'cookie_title' => "Cookie Disclaimer",

    /**
     * Cookie banner description text
     *
     * @default "This website uses cookies to enhance your browsing experience, analyze site traffic, and personalize content. By continuing to use this site, you consent to our use of cookies."
     */
    'cookie_description' => "This website uses cookies to enhance your browsing experience, analyze site traffic, and personalize content. By continuing to use this site, you consent to our use of cookies.",

    /**
     * Accept all cookies button text
     *
     * @default 'Accept all'
     */
    'cookie_accept_btn_text' => 'Accept all',

    /**
     * Reject all cookies button text
     *
     * @default 'Reject all'
     */
    'cookie_reject_btn_text' => 'Reject all',

    /**
     * Manage preferences button text
     *
     * @default 'Manage preferences'
     */
    'cookie_preferences_btn_text' => 'Manage preferences',

    /**
     * Save preferences button text
     *
     * @default 'Save preferences'
     */
    'cookie_preferences_save_text' => 'Save preferences',

    /**
     * Preferences modal title text
     *
     * @default 'Cookie Preferences'
     */
    'cookie_modal_title' => 'Cookie Preferences',

    /**
     * Preferences modal introduction text
     *
     * @default 'You can customize your cookie preferences below.'
     */
    'cookie_modal_intro' => 'You can customize your cookie preferences below.',

    /**
     * Cookie categories configuration
     *
     * Defines the different types of cookies users can manage.
     *
     * @category necessary - Essential cookies that cannot be disabled
     * @category analytics - Cookies used for tracking and analytics
     * @category marketing - Cookies used for advertising
     * @category preferences - Cookies for user preference storage
     */
    'cookie_categories' => [
        'necessary' => [
            'enabled' => true,
            'locked' => true,
            'title' => 'Essential Cookies',
            'description' => 'These cookies are essential for the website to function properly.',
        ],
        'analytics' => [
            'enabled' => env('COOKIE_CONSENT_ANALYTICS', false),
            'locked' => false,
            'js_action' => 'loadGoogleAnalytics',
            'title' => 'Analytics Cookies',
            'description' => 'These cookies help us understand how visitors interact with our website.',
        ],
        'marketing' => [
            'enabled' => env('COOKIE_CONSENT_MARKETING', false),
            'locked' => false,
            'js_action' => 'loadFacebookPixel',
            'title' => 'Marketing Cookies',
            'description' => 'These cookies are used for advertising and tracking purposes.',
        ],
        'preferences' => [
            'enabled' => env('COOKIE_CONSENT_PREFERENCES', false),
            'locked' => false,
            'title' => 'Preferences Cookies',
            'description' => 'These cookies allow the website to remember user preferences.',
        ],
    ],

    /**
     * Policy links configuration
     *
     * Links to legal documents displayed in the cookie banner.
     *
     * Links may be absolute URLs or paths relative to your application; paths are
     * resolved with url() when the banner is rendered. Entries with an empty text or
     * link are not displayed.
     *
     * @item text - Display text for the link
     * @item link - URL or path to the policy document
     */
    'policy_links' => [
        [
            'text' => 'Privacy Policy',
            'link' => env('COOKIE_CONSENT_PRIVACY_POLICY_URL') ?: 'privacy-policy',
        ],
        [
            'text' => 'Terms and Conditions',
            'link' => env('COOKIE_CONSENT_TERMS_URL') ?: 'terms-and-conditions',
        ],
    ],
];

