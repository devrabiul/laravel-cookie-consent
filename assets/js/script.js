"use strict";

(function () {
    // Safe to execute more than once (Turbo and Livewire re-run scripts in <body> on every
    // visit): document listeners are registered once, later executions only re-initialise.
    if (window.cookieConsentScriptLoaded) {
        // Use the first execution's instance (this one's constants are not initialised)
        window.initCookieConsent?.();
        return;
    }
    window.cookieConsentScriptLoaded = true;

    // Maximum accepted length of the preferences cookie, and allowed clock difference
    // (seconds) for the consent timestamp. Must match CookieConsent.php.
    const MAX_COOKIE_LENGTH = 4096;
    const CLOCK_SKEW_TOLERANCE = 86400;

    // Element that had focus before the modal opened, so focus can be restored on close
    let lastFocusedElement = null;

    // Elements are looked up on every use, because SPA navigation can replace them
    function consentRoot() {
        return document.querySelector('.cookie-consent-root');
    }

    function preferencesModal() {
        return document.querySelector('.cookie-preferences-modal');
    }

    function settings() {
        const root = consentRoot();
        const prefix = root?.getAttribute('data-cookie-prefix') || 'cookie_consent';
        let googleConsentTypes = null;
        try {
            const parsed = JSON.parse(root?.getAttribute('data-google-consent-mode') || 'null');
            googleConsentTypes = isPlainObject(parsed) ? parsed : null;
        } catch (e) {
            googleConsentTypes = null;
        }

        return {
            prefix: prefix,
            preferencesCookieName: `${prefix}_preferences`,
            consentVersion: root?.getAttribute('data-consent-version') || '1.0',
            cookieLifetime: parseLifetime(root?.getAttribute('data-cookie-lifetime'), 365),
            rejectLifetime: parseLifetime(root?.getAttribute('data-reject-lifetime'), 7),
            legacyCookiePrefix: root?.getAttribute('data-legacy-cookie-prefix') || null,
            legacyConsentVersion: root?.getAttribute('data-legacy-consent-version') || null,
            googleConsentTypes: googleConsentTypes,
        };
    }

    /**
     * Synchronise the banner, floating button and services with the stored consent.
     * Runs on page load and after every SPA navigation; calling it repeatedly is safe.
     */
    function initCookieConsent() {
        const root = consentRoot();
        if (!root) return;

        // Convert consent saved before consent versioning (year-based cookie names)
        migrateLegacyConsent();

        // Initialize banner
        root.classList.remove('cookie-consent-hide');
        if (root.classList.contains('cookie-disable-interaction')) {
            document.documentElement.classList.add('cookie-disable-interaction');
        }

        // Check existing consent: only a valid record for the current consent version counts
        if (getConsentRecord() !== null) {
            hideConsentBanner();
        } else {
            updateFloatingButton();
        }

        loadEnabledServices();
    }

    function loadEnabledServices() {
        if (typeof window.loadCookieCategoriesEnabledServices === 'function') {
            try {
                window.loadCookieCategoriesEnabledServices();
            } catch (e) {
                console.info(e);
            }
        }
    }

    // Accept handler
    function acceptAll() {
        setAllPreferences('accepted', true);
        hideConsentBanner();
        document.dispatchEvent(new CustomEvent('cookieConsentAccepted'));
        loadEnabledServices();
    }

    // Reject handler
    function rejectAll() {
        setAllPreferences('rejected', false);
        hideConsentBanner();
        document.dispatchEvent(new CustomEvent('cookieConsentRejected'));
    }

    function savePreferences() {
        const preferences = {};
        document.querySelectorAll('.cookie-toggle input[data-category]').forEach(toggle => {
            preferences[toggle.dataset.category] = toggle.checked;
        });

        saveConsent('accepted', preferences);

        hideModal();
        hideConsentBanner();
        document.dispatchEvent(new CustomEvent('cookiePreferencesSaved', { detail: preferences }));
        document.dispatchEvent(new CustomEvent('cookieConsentAccepted'));
        loadEnabledServices();
    }

    // Modal controls
    function setExpanded(expanded) {
        document.querySelectorAll('.preferences-btn, .cookie-consent-floating-button').forEach(button => {
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    }

    function showModal() {
        const modal = preferencesModal();
        if (!modal) return;
        lastFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        document.body.classList.add('modal-open');
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-visible');
        setExpanded(true);

        const savedPreferences = getCookiePreferences();
        document.querySelectorAll('.cookie-toggle input:not([disabled])').forEach(toggle => {
            const category = toggle.dataset.category;
            toggle.checked = savedPreferences ? savedPreferences[category] !== false : true;
        });

        // Move focus into the modal so keyboard/AT users land on the dialog
        const focusTarget = modal.querySelector('[autofocus], .cookie-preferences-modal-close, button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        focusTarget?.focus();
    }

    function hideModal() {
        const modal = preferencesModal();
        if (!modal) return;
        // Focus must leave the modal before it becomes aria-hidden, otherwise assistive
        // technology focus stays trapped inside a hidden element (WAI-ARIA aria-hidden rule).
        if (modal.contains(document.activeElement)) {
            document.activeElement.blur();
        }
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('is-visible');
        document.body.classList.remove('modal-open');
        setExpanded(false);

        // Restore focus to the element that opened the modal
        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus();
        }
        lastFocusedElement = null;
    }

    function showHideToggleCookiePreferencesModal() {
        const modal = preferencesModal();
        if (!modal) return;

        if (modal.classList.contains('is-visible')) {
            hideModal();
        } else {
            showModal();
        }
    }

    function setAllPreferences(status, accept) {
        const preferences = {};
        document.querySelectorAll('.cookie-toggle input[data-category]').forEach(toggle => {
            preferences[toggle.dataset.category] = toggle.disabled ? true : accept;
        });
        saveConsent(status, preferences);
    }

    function hideConsentBanner() {
        const root = consentRoot();
        if (root) {
            root.classList.add('cookie-consent-hide');
            document.documentElement.classList.remove('cookie-disable-interaction');
        }
        hideModal();
        updateFloatingButton();
    }

    // The floating settings button is only shown once the visitor has made a decision
    function updateFloatingButton() {
        const visible = getConsentRecord() !== null;
        document.querySelectorAll('.cookie-consent-floating-button').forEach(button => {
            if (visible) {
                button.removeAttribute('hidden');
            } else {
                button.setAttribute('hidden', '');
            }
        });
    }

    // Events are delegated to the document so they keep working when SPA navigation
    // replaces the banner markup
    const clickActions = [
        ['.cookie-consent-accept', acceptAll],
        ['.cookie-consent-reject', rejectAll],
        ['.cookie-preferences-save', savePreferences],
        ['.preferences-btn', showModal],
        ['.cookie-consent-floating-button', showModal],
        ['.cookie-preferences-modal-overlay', hideModal],
        ['.cookie-preferences-modal-close', hideModal],
    ];

    document.addEventListener('click', function (e) {
        const target = e.target;
        if (!target || typeof target.closest !== 'function') return;

        if (target.closest('.showHideToggleCookiePreferencesModal')) {
            e.preventDefault();
            showHideToggleCookiePreferencesModal();
            return;
        }

        for (const [selector, action] of clickActions) {
            if (target.closest(selector)) {
                action();
                return;
            }
        }
    });

    // Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && preferencesModal()?.classList.contains('is-visible')) {
            hideModal();
        }
    });

    // Helpers
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = "; expires=" + date.toUTCString();
        const secureFlag = location.protocol === 'https:' ? "; Secure" : "";
        document.cookie = `${name}=${value}; path=/; SameSite=Lax${expires}${secureFlag}`;
    }

    function getCookie(name) {
        const nameEQ = name + "=";
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            cookie = cookie.trim();
            if (cookie.indexOf(nameEQ) === 0) {
                return cookie.substring(nameEQ.length);
            }
        }
        return null;
    }

    function readCookie(name) {
        const value = getCookie(name);
        if (value === null || value === '') return null;
        try {
            return decodeURIComponent(value);
        } catch (e) {
            return null;
        }
    }

    function deleteCookie(name) {
        document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax`;
    }

    function parseLifetime(value, fallback) {
        const days = parseFloat(value);
        return Number.isFinite(days) && days > 0 ? days : fallback;
    }

    function isPlainObject(value) {
        return value !== null && typeof value === 'object' && !Array.isArray(value);
    }

    function pickBooleans(object) {
        const result = {};
        Object.keys(object).forEach(key => {
            if (typeof object[key] === 'boolean') result[key] = object[key];
        });
        return result;
    }

    /**
     * Persist a consent decision. Both cookies share the lifetime of the decision:
     * cookie_lifetime when accepted/customised, reject_lifetime when rejected.
     */
    function saveConsent(status, preferences) {
        const config = settings();
        const days = status === 'rejected' ? config.rejectLifetime : config.cookieLifetime;
        const record = {
            version: config.consentVersion,
            status: status,
            timestamp: Math.floor(Date.now() / 1000),
            categories: preferences,
        };

        setCookie(config.prefix, status, days);
        setCookie(config.preferencesCookieName, encodeURIComponent(JSON.stringify(record)), days);
        deleteLegacyCookies();
        updateGoogleConsent();
    }

    /**
     * Read and validate the consent record. Returns null when it is missing, malformed,
     * written for another consent version, or older than its lifetime.
     * Mirrors CookieConsent::consentRecord() in PHP.
     */
    function getConsentRecord() {
        const config = settings();
        const raw = readCookie(config.preferencesCookieName);
        if (raw === null || raw === '' || raw.length > MAX_COOKIE_LENGTH) return null;

        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            return null;
        }

        if (!isPlainObject(data)
            || typeof data.version !== 'string'
            || (data.status !== 'accepted' && data.status !== 'rejected')
            || !Number.isInteger(data.timestamp)
            || data.timestamp <= 0
            || !isPlainObject(data.categories)) {
            return null;
        }

        if (data.version !== config.consentVersion) return null;

        const now = Math.floor(Date.now() / 1000);
        const days = data.status === 'rejected' ? config.rejectLifetime : config.cookieLifetime;
        if (data.timestamp > now + CLOCK_SKEW_TOLERANCE || now - data.timestamp > days * 86400) {
            return null;
        }

        return {
            version: data.version,
            status: data.status,
            timestamp: data.timestamp,
            categories: pickBooleans(data.categories),
        };
    }

    /**
     * Consented categories ({category: boolean}) for the current consent, or null when
     * there is no valid consent. Only categories rendered in the preferences modal are
     * included; locked categories are always true and a rejection grants nothing else.
     * Mirrors CookieConsent::hasConsent() in PHP.
     */
    function getCookiePreferences() {
        const record = getConsentRecord();
        if (record === null) return null;

        const preferences = {};
        document.querySelectorAll('.cookie-toggle input[data-category]').forEach(toggle => {
            const category = toggle.dataset.category;
            preferences[category] = toggle.disabled
                ? true
                : record.status === 'accepted' && record.categories[category] === true;
        });
        return preferences;
    }

    /**
     * Send the visitor's decision to Google Consent Mode v2, when enabled. The defaults
     * ("denied") are set by the snippet rendered in <head>.
     */
    function updateGoogleConsent() {
        const types = settings().googleConsentTypes;
        if (!types) return;

        const preferences = getCookiePreferences() || {};
        const state = {};
        Object.keys(types).forEach(type => {
            state[type] = preferences[types[type]] === true ? 'granted' : 'denied';
        });

        window.dataLayer = window.dataLayer || [];
        if (typeof window.gtag !== 'function') {
            window.gtag = function () {
                window.dataLayer.push(arguments);
            };
        }
        window.gtag('consent', 'update', state);
        window.dataLayer.push({ event: 'cookie_consent_update', cookie_consent: preferences });
    }

    function deleteLegacyCookies() {
        const legacyCookiePrefix = settings().legacyCookiePrefix;
        if (!legacyCookiePrefix) return;
        [legacyCookiePrefix, `${legacyCookiePrefix}_preferences`].forEach(name => {
            if (getCookie(name) !== null) deleteCookie(name);
        });
    }

    /**
     * Convert consent saved by releases before consent versioning ("{prefix}_{year}"
     * cookies). It counts as the legacy consent version, so it is only kept while the
     * configured consent version equals it; otherwise it is discarded.
     * Mirrors CookieConsent::legacyConsentRecord() in PHP.
     */
    function migrateLegacyConsent() {
        const config = settings();
        if (!config.legacyCookiePrefix) return;

        const legacyStatus = getCookie(config.legacyCookiePrefix);
        const legacyPreferences = readCookie(`${config.legacyCookiePrefix}_preferences`);
        if (legacyStatus === null && legacyPreferences === null) return;

        if (config.consentVersion === config.legacyConsentVersion
            && readCookie(config.preferencesCookieName) === null
            && (legacyStatus === 'accepted' || legacyStatus === 'rejected')) {
            let categories = {};
            if (legacyPreferences !== null && legacyPreferences.length <= MAX_COOKIE_LENGTH) {
                try {
                    const parsed = JSON.parse(legacyPreferences);
                    if (isPlainObject(parsed)) categories = pickBooleans(parsed);
                } catch (e) {
                    // Invalid legacy preferences: keep the decision, grant no optional category
                }
            }
            saveConsent(legacyStatus, categories);
        }

        deleteLegacyCookies();
    }

    // Expose globals
    window.getCookie = getCookie;
    window.getCookiePreferences = getCookiePreferences;
    // Expose globally for inline onclick
    window.showHideToggleCookiePreferencesModal = showHideToggleCookiePreferencesModal;
    // Re-initialise manually, e.g. after a custom client-side navigation
    window.initCookieConsent = initCookieConsent;

    // SPA support: Turbo, Livewire (wire:navigate) and Inertia. Each init is idempotent.
    document.addEventListener('turbo:load', initCookieConsent);
    document.addEventListener('livewire:navigated', initCookieConsent);
    document.addEventListener('inertia:navigate', initCookieConsent);
    // Close the modal before Turbo caches the page, so restored pages are not left open
    document.addEventListener('turbo:before-cache', () => {
        if (preferencesModal()?.classList.contains('is-visible')) hideModal();
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCookieConsent);
    } else {
        initCookieConsent();
    }

    // Prevent layout shift on modal open
    document.head.insertAdjacentHTML('beforeend', `
        <style>
            .modal-open {
                overflow: hidden;
                padding-right: var(--scrollbar-width, 0);
            }
        </style>
    `);
})();
