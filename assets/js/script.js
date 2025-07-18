"use strict";

document.addEventListener('DOMContentLoaded', function () {
    // DOM Elements
    const consentRoot = document.querySelector('.cookie-consent-root');
    const acceptButtons = document.querySelectorAll('.cookie-consent-accept');
    const rejectButtons = document.querySelectorAll('.cookie-consent-reject');
    const cookieConsentPrefix = consentRoot?.getAttribute('data-cookie-prefix') || 'cookie_consent';
    const preferencesCookieName = `${cookieConsentPrefix}_preferences`;

    // Modal Elements
    const preferencesBtn = document.querySelector('.preferences-btn');
    const modal = document.querySelector('.cookie-preferences-modal');
    const modalOverlay = document.querySelector('.cookie-preferences-modal-overlay');
    const modalClose = document.querySelector('.cookie-preferences-modal-close');
    const modalSave = document.querySelector('.cookie-preferences-save');

    // Initialize banner
    if (consentRoot) {
        consentRoot.classList.remove('cookie-consent-hide');
        if (consentRoot.classList.contains('cookie-disable-interaction')) {
            document.documentElement.classList.add('cookie-disable-interaction');
        }
    }

    // Check existing consent
    const cookieConsent = getCookie(cookieConsentPrefix);
    if (cookieConsent === 'accepted' || cookieConsent === 'rejected') {
        hideConsentBanner();
    }

    // Accept handler
    acceptButtons.forEach(button => {
        button.addEventListener('click', () => {
            setCookie(cookieConsentPrefix, 'accepted', consentRoot?.getAttribute('data-cookie-lifetime') || 7);
            setAllPreferences(true);
            hideConsentBanner();
            document.dispatchEvent(new CustomEvent('cookieConsentAccepted'));

            if (typeof loadCookieCategoriesEnabledServices === 'function') {
                try {
                    loadCookieCategoriesEnabledServices();
                } catch (e) {
                    console.info(e);
                }
            }
        });
    });

    // Reject handler
    rejectButtons.forEach(button => {
        button.addEventListener('click', () => {
            setCookie(cookieConsentPrefix, 'rejected', consentRoot?.getAttribute('data-reject-lifetime') || 1);
            setAllPreferences(false);
            hideConsentBanner();
            document.dispatchEvent(new CustomEvent('cookieConsentRejected'));
        });
    });

    // Modal controls
    function showModal() {
        if (!modal) return;
        document.body.classList.add('modal-open');
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-visible');

        const savedPreferences = getCookiePreferences();
        document.querySelectorAll('.cookie-toggle input:not([disabled])').forEach(toggle => {
            const category = toggle.dataset.category;
            toggle.checked = savedPreferences ? savedPreferences[category] !== false : true;
        });
    }

    function hideModal() {
        if (!modal) return;
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('is-visible');
        document.body.classList.remove('modal-open');
    }

    function setAllPreferences(accept) {
        const preferences = {};
        document.querySelectorAll('.cookie-toggle input[data-category]').forEach(toggle => {
            preferences[toggle.dataset.category] = toggle.disabled ? true : accept;
        });
        setCookiePreferences(preferences);
    }

    // Modal events
    preferencesBtn?.addEventListener('click', showModal);
    modalOverlay?.addEventListener('click', hideModal);
    modalClose?.addEventListener('click', hideModal);

    modalSave?.addEventListener('click', () => {
        const preferences = {};
        document.querySelectorAll('.cookie-toggle input[data-category]').forEach(toggle => {
            preferences[toggle.dataset.category] = toggle.checked;
        });

        setCookiePreferences(preferences);
        setCookie(cookieConsentPrefix, 'accepted', consentRoot?.getAttribute('data-cookie-lifetime') || 7);

        hideModal();
        hideConsentBanner();
        document.dispatchEvent(new CustomEvent('cookiePreferencesSaved', { detail: preferences }));
        document.dispatchEvent(new CustomEvent('cookieConsentAccepted'));

        if (typeof loadCookieCategoriesEnabledServices === 'function') {
            try {
                loadCookieCategoriesEnabledServices();
            } catch (e) {
                console.info(e);
            }
        }
    });

    // Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal?.classList.contains('is-visible')) {
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

    function setCookiePreferences(preferences) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (365 * 24 * 60 * 60 * 1000));
        const secureFlag = location.protocol === 'https:' ? "; Secure" : "";
        document.cookie = `${preferencesCookieName}=${JSON.stringify(preferences)}; expires=${expires.toUTCString()}; path=/; SameSite=Lax${secureFlag}`;
    }

    function getCookiePreferences() {
        const name = `${preferencesCookieName}=`;
        const decoded = decodeURIComponent(document.cookie);
        const cookies = decoded.split(';');
        for (let cookie of cookies) {
            cookie = cookie.trim();
            if (cookie.indexOf(name) === 0) {
                try {
                    return JSON.parse(cookie.substring(name.length));
                } catch (e) {
                    console.warn('Invalid preferences cookie');
                }
            }
        }
        return null;
    }

    function hideConsentBanner() {
        if (consentRoot) {
            consentRoot.classList.add('cookie-consent-hide');
            document.documentElement.classList.remove('cookie-disable-interaction');
        }
        hideModal();
    }

    // Expose globals
    window.getCookie = getCookie;
    window.getCookiePreferences = getCookiePreferences;

    function showHideToggleCookiePreferencesModal() {
        const modal = document.querySelector('.cookie-preferences-modal');
        if (!modal) return;

        const isVisible = modal.classList.contains('is-visible');
        if (isVisible) {
            // Hide modal
            modal.setAttribute('aria-hidden', 'true');
            modal.classList.remove('is-visible');
            document.body.classList.remove('modal-open');
        } else {
            // Show modal
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.add('is-visible');
            document.body.classList.add('modal-open');

            // Initialize toggles if needed
            const savedPreferences = window.getCookiePreferences?.();
            document.querySelectorAll('.cookie-toggle input:not([disabled])').forEach(toggle => {
                const category = toggle.dataset.category;
                toggle.checked = savedPreferences ? savedPreferences[category] !== false : true;
            });
        }
    }

    // Expose globally for inline onclick
    window.showHideToggleCookiePreferencesModal = showHideToggleCookiePreferencesModal;

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('showHideToggleCookiePreferencesModal')) {
            e.preventDefault();
            showHideToggleCookiePreferencesModal();
        }
    });

});

// Prevent layout shift on modal open
document.head.insertAdjacentHTML('beforeend', `
    <style>
        .modal-open {
            overflow: hidden;
            padding-right: var(--scrollbar-width, 0);
        }
    </style>
`);
