<!-- Main Cookie Consent Banner -->
<div class="cookie-consent-root
    cookie-consent-hide
    {{ $cookieConfig['disable_page_interaction'] ? 'cookie-disable-interaction' : '' }}
    consent-layout-{{ $cookieConfig['consent_modal_layout'] ?? 'bar' }}
    theme-{{ $cookieConfig['theme'] ?? 'default' }}"
     data-cookie-prefix="{{ Str::slug($cookieConfig['cookie_prefix']) }}_{{ date('Y') }}"
     data-cookie-lifetime="{{ $cookieConfig['cookie_lifetime'] }}"
     data-reject-lifetime="{{ $cookieConfig['reject_lifetime'] }}"
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

    @if (isset($cookieConfig['policy_links']) && count($cookieConfig['policy_links']) > 0)
        <div class="cookie-consent-links-container">
            <ul class="cookie-consent-links-list">
                @foreach ($cookieConfig['policy_links'] as $policyLinks)
                    <li class="cookie-consent-link-item">
                        <a target="_blank" rel="noopener noreferrer" href="{{ $policyLinks['link'] }}"
                           class="cookie-consent-link">
                            {{ $policyLinks['text'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<!-- Cookie Preferences Modal -->
<div id="cookie-preferences-modal" class="cookie-preferences-modal" aria-hidden="true">
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

{!! CookieConsent::scriptsPath() !!}

<script type="text/javascript">
    "use strict";
    // Load analytics/tracking services based on preferences

    // Then define your service loader
    window.loadCookieCategoriesEnabledServices = function () {
        const preferences = getCookiePreferences();
        if (!preferences) return;

        @foreach ($cookieConfig['cookie_categories'] as $category => $details)
            @if(isset($details['js_action']))
                try {
                    if (preferences?.{!! Str::slug($category) !!}) {
                        const action = {!! json_encode($details['js_action']) !!};
                        if (typeof window[action] === "function") {
                            window[action]();
                        }
                    }
                } catch (exception) {
                    console.info(exception)
                }
            @endif
        @endforeach
    }

    document.addEventListener('DOMContentLoaded', function () {
        try {
            loadCookieCategoriesEnabledServices();
        } catch (e) {
            console.info(e);
        }
    })
</script>
