<!-- Main Cookie Consent Banner -->
<div class="cookie-consent-root
    cookie-consent-hide
    {{ $cookieConfig['disable_page_interaction'] ? 'cookie-disable-interaction' : '' }}
    consent-layout-{{ $cookieConfig['consent_modal_layout'] ?? 'bar' }}
    theme-{{ $cookieConfig['theme'] ?? 'default' }}
    {{ $cardBgColor ? 'cookie-consent-custom-bg' : '' }}"
     data-cookie-prefix="{{ $consentCookieName }}"
     data-consent-version="{{ $consentVersion }}"
     data-cookie-lifetime="{{ $cookieLifetime }}"
     data-reject-lifetime="{{ $rejectLifetime }}"
     data-legacy-cookie-prefix="{{ $legacyCookieName }}"
     data-legacy-consent-version="{{ $legacyConsentVersion }}"
     @if ($googleConsentTypes !== null) data-google-consent-mode="{{ json_encode((object) $googleConsentTypes) }}" @endif
     @if ($cardStyle !== '') style="{{ $cardStyle }}" @endif
     role="dialog"
     aria-modal="true"
     aria-label="Cookie consent banner"
>
    <div class="cookie-consent-container">
        <div class="cookie-consent-content-container">
            <div class="cookie-consent-content">
                <h2 class="cookie-consent-content-title">
                    {{ $cookieConfig['cookie_title'] }}
                </h2>
                <div class="cookie-consent-content-description">
                    <p>{{ $cookieConfig['cookie_description'] }}</p>
                </div>
            </div>

            <div class="cookie-consent-button-container">
                <div class="cookie-consent-button-action {{ $cookieConfig['flip_button'] ? 'flip-button' : '' }}">
                    <button type="button" class="cookie-consent-accept" aria-label="Accept all cookies">
                        {{ $cookieConfig['cookie_accept_btn_text'] }}
                    </button>
                    <button type="button" class="cookie-consent-reject" aria-label="Reject all cookies">
                        {{ $cookieConfig['cookie_reject_btn_text'] }}
                    </button>
                </div>
                @if ($cookieConfig['preferences_modal_enabled'])
                    <button type="button" class="preferences-btn" aria-expanded="false"
                            aria-controls="cookie-preferences-modal">
                        {{ $cookieConfig['cookie_preferences_btn_text'] }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if (count($policyLinks) > 0)
        <div class="cookie-consent-links-container">
            <ul class="cookie-consent-links-list">
                @foreach ($policyLinks as $policyLink)
                    <li class="cookie-consent-link-item">
                        <a target="_blank" rel="noopener noreferrer" href="{{ $policyLink['link'] }}"
                           class="cookie-consent-link">
                            {{ $policyLink['text'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<!-- Cookie Preferences Modal -->
<div id="cookie-preferences-modal" class="cookie-preferences-modal" aria-hidden="true"
     @if ($cardStyle !== '') style="{{ $cardStyle }}" @endif>
    <div class="cookie-preferences-modal-overlay" tabindex="-1"></div>
    <div class="cookie-preferences-modal-content" role="document">
        <div class="cookie-preferences-modal-header">
            <h2 id="cookie-modal-title" class="cookie-preferences-modal-title">
                {{ $cookieConfig['cookie_modal_title'] }}
            </h2>
            <button type="button" class="cookie-preferences-modal-close" aria-label="Close cookie preferences">
                <svg width="12" height="12" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"
                     aria-hidden="true">
                    <path d="M12 4L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
        <div class="cookie-preferences-modal-body">
            <p class="cookie-preferences-intro">
                {{ $cookieConfig['cookie_modal_intro'] }}
            </p>

            <div class="cookie-categories">
                @foreach ($cookieConfig['cookie_categories'] as $category => $details)
                    @if ($details['enabled'])
                        <div class="cookie-category cookie-category-{{ $category }}">
                            <div class="cookie-category-header">
                                <h3 class="cookie-category-title">{{ $details['title'] }}</h3>
                                <label class="cookie-toggle">
                                    <input type="checkbox"
                                           {{ $details['locked'] ? 'disabled checked' : '' }}
                                           data-category="{{ $category }}"
                                           aria-label="{{ $details['title'] }} toggle">
                                    <span class="cookie-toggle-slider"></span>
                                </label>
                            </div>
                            <p class="cookie-category-description">{{ $details['description'] }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="cookie-preferences-modal-footer">
            <div class="cookie-preferences-modal-button-group">
                <button type="button" class="cookie-consent-accept primary-button">
                    {{ $cookieConfig['cookie_accept_btn_text'] }}
                </button>
                <button type="button" class="cookie-consent-reject primary-button">
                    {{ $cookieConfig['cookie_reject_btn_text'] }}
                </button>
            </div>
            <div class="cookie-preferences-modal-save">
                <button type="button" class="cookie-preferences-save primary-button">
                    {{ $cookieConfig['cookie_preferences_save_text'] }}
                </button>
            </div>
        </div>
    </div>
</div>

@if ($floatingButton)
    <!-- Floating Cookie Settings Button (shown once the visitor has made a choice) -->
    <button type="button"
            class="cookie-consent-floating-button cookie-consent-floating-{{ $floatingButton['position'] }}"
            aria-label="{{ $floatingButton['label'] }}"
            title="{{ $floatingButton['label'] }}"
            aria-controls="cookie-preferences-modal"
            aria-expanded="false"
            @if ($cardStyle !== '') style="{{ $cardStyle }}" @endif
            hidden>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M21.95 12.7A10 10 0 1 1 11.3 2.05a3 3 0 0 0 3.65 3.65 3 3 0 0 0 3.35 3.35 3 3 0 0 0 3.65 3.65Z"
                  stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            <circle cx="8.5" cy="9.5" r="1.25" fill="currentColor"/>
            <circle cx="15.5" cy="15" r="1.25" fill="currentColor"/>
            <circle cx="9" cy="15.5" r="1.25" fill="currentColor"/>
            <circle cx="12.5" cy="12" r="0.9" fill="currentColor"/>
        </svg>
    </button>
@endif

{!! CookieConsent::scriptsPath() !!}

@php
    $cookieConsentThemePreset = config('laravel-cookie-consent.theme_preset');
@endphp

@if (isset($cookieConsentThemePreset) && !is_null($cookieConsentThemePreset))
<script>
    document.documentElement.setAttribute('cookie-consent-theme-preset', @json($cookieConsentThemePreset));
</script>
@endif

<script type="text/javascript">
    "use strict";
    // Load analytics/tracking services based on preferences

    // Then define your service loader. It runs on page load, after every SPA navigation
    // and after each consent decision; each js_action is called at most once per page.
    window.cookieConsentLoadedActions = window.cookieConsentLoadedActions || {};
    window.loadCookieCategoriesEnabledServices = function () {
        const preferences = getCookiePreferences();
        if (!preferences) return;

        @foreach ($cookieConfig['cookie_categories'] as $category => $details)
            @if(isset($details['js_action']))
                try {
                    if (preferences[@json((string) $category)] === true) {
                        const action = @json($details['js_action']);
                        if (typeof window[action] === "function" && !window.cookieConsentLoadedActions[action]) {
                            window.cookieConsentLoadedActions[action] = true;
                            window[action]();
                        }
                    }
                } catch (exception) {
                    console.info(exception)
                }
            @endif
        @endforeach
    }
</script>
