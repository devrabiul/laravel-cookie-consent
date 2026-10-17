/*
 * Google Consent Mode v2 defaults. Inlined into <head> by CookieConsent::googleConsentMode()
 * after `window.cookieConsentGoogleConsentMode` (settings generated from the config).
 *
 * It must run before gtag.js / Google Tag Manager load: it sets every consent type to
 * "denied" (locked categories such as "necessary" to "granted") and then applies the
 * visitor's stored consent, so returning visitors are not tracked as denied first.
 * Later decisions are sent by assets/js/script.js. The consent record is validated with
 * the same rules as script.js and CookieConsent::consentRecord().
 */
(function (config) {
    "use strict";

    if (!config || typeof config.types !== 'object' || config.types === null) return;

    window.dataLayer = window.dataLayer || [];
    if (typeof window.gtag !== 'function') {
        window.gtag = function () {
            window.dataLayer.push(arguments);
        };
    }

    function isPlainObject(value) {
        return value !== null && typeof value === 'object' && !Array.isArray(value);
    }

    function readCookie(name) {
        var cookies = document.cookie.split(';');
        for (var i = 0; i < cookies.length; i++) {
            var cookie = cookies[i].trim();
            if (cookie.indexOf(name + '=') === 0) {
                var value = cookie.substring(name.length + 1);
                if (value === '') return null;
                try {
                    return decodeURIComponent(value);
                } catch (e) {
                    return null;
                }
            }
        }
        return null;
    }

    function readRecord() {
        var raw = readCookie(config.cookieName);
        if (raw === null || raw.length > config.maxCookieLength) return null;

        var data;
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
            || !isPlainObject(data.categories)
            || data.version !== config.version) {
            return null;
        }

        var now = Math.floor(Date.now() / 1000);
        var days = data.status === 'rejected' ? config.rejectLifetime : config.cookieLifetime;
        if (data.timestamp > now + config.clockSkewTolerance || now - data.timestamp > days * 86400) {
            return null;
        }

        return data;
    }

    function consentState(record) {
        var state = {};
        Object.keys(config.types).forEach(function (type) {
            var category = config.types[type];
            var granted = config.grantedCategories.indexOf(category) !== -1
                || (record !== null
                    && record.status === 'accepted'
                    && record.categories[category] === true
                    && config.enabledCategories.indexOf(category) !== -1);
            state[type] = granted ? 'granted' : 'denied';
        });
        return state;
    }

    var defaults = consentState(null);
    if (config.waitForUpdate > 0) {
        defaults.wait_for_update = config.waitForUpdate;
    }
    window.gtag('consent', 'default', defaults);

    if (config.adsDataRedaction) window.gtag('set', 'ads_data_redaction', true);
    if (config.urlPassthrough) window.gtag('set', 'url_passthrough', true);

    var record = readRecord();
    if (record !== null) {
        window.gtag('consent', 'update', consentState(record));
    }
})(window.cookieConsentGoogleConsentMode);
