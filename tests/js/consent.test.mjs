// Behaviour tests for assets/js/script.js. Run with `composer test:js` (Node 18+).
//
// The script runs inside a minimal DOM shim, so no npm dependencies are required.
// Consent validity is checked against tests/fixtures/consent-cases.json, the same
// scenarios the PHP suite uses for CookieConsent::hasConsent().

import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const root = new URL('../../', import.meta.url);
const SCRIPT = readFileSync(new URL('assets/js/script.js', root), 'utf8');
const FIXTURES = JSON.parse(readFileSync(new URL('tests/fixtures/consent-cases.json', root), 'utf8'));

const DAY = 86400;
const slug = 'laravel-app'; // Str::slug('Laravel_App')
const year = new Date().getFullYear();
const NAMES = {
    '{consent}': `${slug}_consent`,
    '{preferences}': `${slug}_consent_preferences`,
    '{legacy}': `${slug}_${year}`,
    '{legacy_preferences}': `${slug}_${year}_preferences`,
    '{legacy_previous}': `${slug}_${year - 1}`,
    '{legacy_previous_preferences}': `${slug}_${year - 1}_preferences`,
};

const HEAD_SNIPPET = readFileSync(new URL('resources/js/google-consent-mode.js', root), 'utf8');

const GOOGLE_TYPES = {
    ad_storage: 'marketing',
    ad_user_data: 'marketing',
    ad_personalization: 'marketing',
    analytics_storage: 'analytics',
    functionality_storage: 'preferences',
    personalization_storage: 'preferences',
    security_storage: 'necessary',
};

function classList(initial = []) {
    const set = new Set(initial);
    return {
        add: c => set.add(c),
        remove: c => set.delete(c),
        contains: c => set.has(c),
    };
}

function element(classes = [], extra = {}) {
    return {
        classes,
        classList: classList(classes),
        attributes: {},
        setAttribute(name, value) { this.attributes[name] = String(value); },
        getAttribute(name) { return this.attributes[name] ?? null; },
        removeAttribute(name) { delete this.attributes[name]; },
        hasAttribute(name) { return name in this.attributes; },
        // Single class selectors only, which is all script.js uses with closest()
        closest(selector) { return this.classes.includes(selector.replace(/^\./, '')) ? this : null; },
        contains: () => false,
        querySelector: () => null,
        focus() {},
        ...extra,
    };
}

function rootElement(settings) {
    const consentRoot = element(['cookie-consent-root', 'cookie-consent-hide']);
    Object.assign(consentRoot.attributes, {
        'data-cookie-prefix': NAMES['{consent}'],
        'data-consent-version': String(settings.consent_version),
        'data-cookie-lifetime': String(settings.cookie_lifetime),
        'data-reject-lifetime': String(settings.reject_lifetime),
        'data-legacy-cookie-prefix': `${slug}_${year}`,
        'data-legacy-consent-version': '1.0',
    });
    if (settings.google_consent_mode) {
        consentRoot.attributes['data-google-consent-mode'] = JSON.stringify(GOOGLE_TYPES);
    }
    return consentRoot;
}

/**
 * Create a browser-like environment. Cookies are given URL-encoded, as the browser stores them.
 */
function environment({ cookies = {}, readyState = 'loading' } = {}) {
    const jar = new Map(Object.entries(cookies));
    const writes = [];
    const listeners = {};
    const dom = {
        root: null,
        modal: element(['cookie-preferences-modal']),
        floating: [element(['cookie-consent-floating-button'])],
        accept: element(['cookie-consent-accept']),
        reject: element(['cookie-consent-reject']),
        save: element(['cookie-preferences-save']),
        // Toggles rendered for enabled categories; locked ones are disabled
        toggles: Object.entries(FIXTURES.categories)
            .filter(([, definition]) => definition.enabled)
            .map(([category, definition]) => ({ dataset: { category }, disabled: definition.locked, checked: definition.locked })),
    };
    dom.floating[0].attributes.hidden = '';

    const document = {
        readyState,
        get cookie() {
            return [...jar].map(([name, value]) => `${name}=${value}`).join('; ');
        },
        set cookie(string) {
            writes.push(string);
            const [pair, ...attributes] = string.split(';').map(part => part.trim());
            const index = pair.indexOf('=');
            const name = pair.slice(0, index);
            const expires = attributes.find(a => a.startsWith('expires='));
            if (expires && new Date(expires.slice(8)).getTime() <= Date.now()) {
                jar.delete(name);
            } else {
                jar.set(name, pair.slice(index + 1));
            }
        },
        querySelector(selector) {
            return {
                '.cookie-consent-root': dom.root,
                '.cookie-preferences-modal': dom.modal,
            }[selector] ?? null;
        },
        querySelectorAll(selector) {
            if (selector === '.cookie-toggle input[data-category]') return dom.toggles;
            if (selector === '.cookie-toggle input:not([disabled])') return dom.toggles.filter(t => !t.disabled);
            if (selector === '.cookie-consent-floating-button') return dom.floating;
            if (selector === '.preferences-btn, .cookie-consent-floating-button') return dom.floating;
            return [];
        },
        addEventListener(type, cb) { (listeners[type] ||= []).push(cb); },
        dispatchEvent() {},
        documentElement: element(),
        body: element(),
        head: { insertAdjacentHTML() {} },
        activeElement: null,
    };

    const window = {};
    const context = vm.createContext({
        document,
        window,
        location: { protocol: 'https:' },
        CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options?.detail; } },
        HTMLElement: class {},
        console,
    });

    return {
        window, document, jar, writes, dom, listeners,
        run(source) { vm.runInContext(source, context); },
        fire(type) { (listeners[type] || []).forEach(cb => cb({ type })); },
        click(target) {
            let prevented = false;
            (listeners.click || []).forEach(cb => cb({ target, preventDefault() { prevented = true; } }));
            return prevented;
        },
    };
}

/**
 * Load script.js with the given config (as rendered by the Blade view) and cookies,
 * then fire DOMContentLoaded.
 */
function boot({ config = {}, cookies = {} } = {}) {
    const settings = { consent_version: '1.0', cookie_lifetime: 365, reject_lifetime: 7, ...config };
    const env = environment({ cookies });
    env.dom.root = rootElement(settings);
    env.run(SCRIPT);
    env.fire('DOMContentLoaded');

    return {
        ...env,
        consentRoot: env.dom.root,
        toggles: env.dom.toggles,
        acceptButton: { click: () => env.click(env.dom.accept) },
        rejectButton: { click: () => env.click(env.dom.reject) },
        saveButton: { click: () => env.click(env.dom.save) },
    };
}

/**
 * Run the Google Consent Mode head snippet with settings as CookieConsent::googleConsentMode()
 * generates them, and return the dataLayer.
 */
function runHeadSnippet({ config = {}, cookies = {} } = {}) {
    const settings = { consent_version: '1.0', cookie_lifetime: 365, reject_lifetime: 7, ...config };
    const env = environment({ cookies });
    env.window.cookieConsentGoogleConsentMode = {
        cookieName: NAMES['{preferences}'],
        version: String(settings.consent_version),
        cookieLifetime: settings.cookie_lifetime,
        rejectLifetime: settings.reject_lifetime,
        maxCookieLength: 4096,
        clockSkewTolerance: 86400,
        types: GOOGLE_TYPES,
        enabledCategories: Object.keys(FIXTURES.categories).filter(c => FIXTURES.categories[c].enabled),
        grantedCategories: Object.keys(FIXTURES.categories).filter(c => FIXTURES.categories[c].enabled && FIXTURES.categories[c].locked),
        waitForUpdate: 500,
        adsDataRedaction: true,
        urlPassthrough: false,
    };
    env.run(HEAD_SNIPPET);
    return plain(env.window.dataLayer.map(entry => Array.from(entry)));
}

function googleState(expected) {
    const state = {};
    for (const [type, category] of Object.entries(GOOGLE_TYPES)) {
        state[type] = expected[category] ? 'granted' : 'denied';
    }
    return state;
}

function fixtureCookies(spec, now = Math.floor(Date.now() / 1000)) {
    const cookies = {};
    for (const [token, value] of Object.entries(spec)) {
        let decoded;
        if ('raw' in value) {
            decoded = value.raw;
        } else {
            const record = { ...value.json };
            if ('age_days' in record) {
                record.timestamp = Math.floor(now - record.age_days * DAY);
                delete record.age_days;
            }
            if ('padding' in record) record.padding = 'x'.repeat(record.padding);
            decoded = JSON.stringify(record);
        }
        // Legacy releases wrote the preferences JSON unencoded; the current release encodes it
        cookies[NAMES[token]] = token.startsWith('{legacy') ? decoded : encodeURIComponent(decoded);
    }
    return cookies;
}

// Objects created inside the vm context have a different Object prototype
const plain = value => JSON.parse(JSON.stringify(value));

function readRecord(jar) {
    return JSON.parse(decodeURIComponent(jar.get(NAMES['{preferences}'])));
}

function expiresInDays(writes, name) {
    const write = [...writes].reverse().find(w => w.startsWith(`${name}=`));
    const expires = write.split(';').map(p => p.trim()).find(p => p.startsWith('expires='));
    return (new Date(expires.slice(8)).getTime() - Date.now()) / (DAY * 1000);
}

for (const fixture of FIXTURES.cases) {
    test(`shared case: ${fixture.name}`, () => {
        const { window, consentRoot } = boot({ config: fixture.config, cookies: fixtureCookies(fixture.cookies) });
        const preferences = window.getCookiePreferences();
        const expected = fixture.expected;

        assert.equal(preferences !== null, expected.valid, 'valid');
        assert.equal(consentRoot.classList.contains('cookie-consent-hide'), expected.valid, 'banner hidden');
        for (const category of ['necessary', 'analytics', 'marketing', 'preferences']) {
            assert.equal(Boolean(preferences?.[category]), expected[category], category);
        }
    });

    test(`shared case (Consent Mode head snippet): ${fixture.name}`, () => {
        const dataLayer = runHeadSnippet({ config: fixture.config, cookies: fixtureCookies(fixture.cookies) });
        const noConsent = { necessary: true, analytics: false, marketing: false, preferences: false };

        assert.deepEqual(dataLayer[0], ['consent', 'default', { ...googleState(noConsent), wait_for_update: 500 }]);
        assert.deepEqual(dataLayer[1], ['set', 'ads_data_redaction', true]);

        // Legacy consent is converted (and sent) later by script.js, not by the head snippet
        const appliesInHead = fixture.expected.valid && '{preferences}' in fixture.cookies;
        assert.deepEqual(dataLayer.slice(2), appliesInHead ? [['consent', 'update', googleState(fixture.expected)]] : []);
    });
}

test('accepting stores a versioned record for cookie_lifetime days', () => {
    const { acceptButton, jar, writes, window } = boot({ config: { cookie_lifetime: 30, consent_version: '3.1' } });
    acceptButton.click();

    const record = readRecord(jar);
    assert.equal(record.version, '3.1');
    assert.equal(record.status, 'accepted');
    assert.ok(Number.isInteger(record.timestamp));
    assert.deepEqual(record.categories, { necessary: true, analytics: true, marketing: true });
    assert.equal(jar.get(NAMES['{consent}']), 'accepted');
    assert.ok(Math.abs(expiresInDays(writes, NAMES['{preferences}']) - 30) < 0.01);
    assert.ok(Math.abs(expiresInDays(writes, NAMES['{consent}']) - 30) < 0.01);
    assert.deepEqual(plain(window.getCookiePreferences()), { necessary: true, analytics: true, marketing: true });
});

test('rejecting stores the decision for reject_lifetime days', () => {
    const { rejectButton, jar, writes, window } = boot({ config: { reject_lifetime: 2 } });
    rejectButton.click();

    const record = readRecord(jar);
    assert.equal(record.status, 'rejected');
    assert.deepEqual(record.categories, { necessary: true, analytics: false, marketing: false });
    assert.ok(Math.abs(expiresInDays(writes, NAMES['{preferences}']) - 2) < 0.01);
    assert.ok(Math.abs(expiresInDays(writes, NAMES['{consent}']) - 2) < 0.01);
    assert.deepEqual(plain(window.getCookiePreferences()), { necessary: true, analytics: false, marketing: false });
});

test('saving preferences stores the chosen categories', () => {
    const { saveButton, toggles, jar, writes } = boot({ config: { cookie_lifetime: 10 } });
    toggles.find(t => t.dataset.category === 'analytics').checked = true;
    toggles.find(t => t.dataset.category === 'marketing').checked = false;
    saveButton.click();

    const record = readRecord(jar);
    assert.equal(record.status, 'accepted');
    assert.deepEqual(record.categories, { necessary: true, analytics: true, marketing: false });
    assert.ok(Math.abs(expiresInDays(writes, NAMES['{preferences}']) - 10) < 0.01);
});

test('invalid lifetimes fall back to the defaults', () => {
    const { acceptButton, writes } = boot({ config: { cookie_lifetime: 'abc' } });
    acceptButton.click();

    assert.ok(Math.abs(expiresInDays(writes, NAMES['{preferences}']) - 365) < 0.01);
});

test('a stored decision is re-requested after the consent version changes', () => {
    const first = boot({ config: { consent_version: '1.0' } });
    first.acceptButton.click();
    const cookies = Object.fromEntries(first.jar);

    assert.notEqual(boot({ config: { consent_version: '1.0' }, cookies }).window.getCookiePreferences(), null);

    const second = boot({ config: { consent_version: '1.1' }, cookies });
    assert.equal(second.window.getCookiePreferences(), null);
    assert.equal(second.consentRoot.classList.contains('cookie-consent-hide'), false);
});

test('legacy consent is migrated to the versioned cookies at version 1.0', () => {
    const { jar } = boot({
        cookies: fixtureCookies({
            '{legacy}': { raw: 'accepted' },
            '{legacy_preferences}': { raw: '{"necessary":true,"analytics":true,"marketing":false}' },
        }),
    });

    assert.equal(jar.has(NAMES['{legacy}']), false);
    assert.equal(jar.has(NAMES['{legacy_preferences}']), false);
    const record = readRecord(jar);
    assert.equal(record.version, '1.0');
    assert.equal(record.status, 'accepted');
    assert.deepEqual(record.categories, { necessary: true, analytics: true, marketing: false });
});

test('legacy consent is discarded once the version has changed', () => {
    const { jar, window } = boot({
        config: { consent_version: '2.0' },
        cookies: fixtureCookies({ '{legacy}': { raw: 'accepted' }, '{legacy_preferences}': { raw: '{"analytics":true}' } }),
    });

    assert.equal(jar.has(NAMES['{legacy}']), false);
    assert.equal(jar.has(NAMES['{preferences}']), false);
    assert.equal(window.getCookiePreferences(), null);
});

test('malformed percent-encoding in other cookies does not break parsing', () => {
    const cookies = fixtureCookies({
        '{preferences}': { json: { version: '1.0', status: 'accepted', age_days: 1, categories: { analytics: true } } },
    });
    const { window } = boot({ cookies: { broken: '%E0%A4%A', ...cookies } });

    assert.equal(window.getCookiePreferences()?.analytics, true);
});

test('the script never assigns window.onload', () => {
    const { window } = boot();

    assert.equal('onload' in window, false);
});

test('initialises immediately when loaded after DOMContentLoaded', () => {
    const env = environment({ readyState: 'complete' });
    env.dom.root = rootElement({ consent_version: '1.0', cookie_lifetime: 365, reject_lifetime: 7 });
    env.run(SCRIPT);

    assert.equal(env.dom.root.classList.contains('cookie-consent-hide'), false);
});

test('re-executing the script (Turbo / Livewire) re-initialises without duplicate listeners', () => {
    const app = boot();
    const clickListeners = app.listeners.click.length;

    app.acceptButton.click();
    // New page: the banner markup is replaced and body scripts run again
    app.dom.root = rootElement({ consent_version: '1.0', cookie_lifetime: 365, reject_lifetime: 7 });
    app.document.readyState = 'complete';
    app.run(SCRIPT);

    assert.equal(app.listeners.click.length, clickListeners);
    assert.equal(app.dom.root.classList.contains('cookie-consent-hide'), true);
    assert.equal(app.jar.get(NAMES['{consent}']), 'accepted');
});

for (const event of ['turbo:load', 'livewire:navigated', 'inertia:navigate']) {
    test(`re-initialises after ${event}`, () => {
        const app = boot();
        app.acceptButton.click();

        app.dom.root = rootElement({ consent_version: '1.0', cookie_lifetime: 365, reject_lifetime: 7 });
        app.dom.floating = [element(['cookie-consent-floating-button'])];
        app.dom.floating[0].attributes.hidden = '';
        app.fire(event);

        assert.equal(app.dom.root.classList.contains('cookie-consent-hide'), true);
        assert.equal(app.dom.floating[0].hasAttribute('hidden'), false);
    });
}

test('buttons in replaced markup keep working (delegated events)', () => {
    const app = boot();
    app.dom.root = rootElement({ consent_version: '1.0', cookie_lifetime: 365, reject_lifetime: 7 });
    app.fire('turbo:load');

    app.click(element(['cookie-consent-reject']));
    assert.equal(app.jar.get(NAMES['{consent}']), 'rejected');
});

test('loads enabled services on every initialisation', () => {
    const app = boot();
    let calls = 0;
    app.window.loadCookieCategoriesEnabledServices = () => { calls++; };

    app.fire('turbo:load');
    app.fire('livewire:navigated');

    assert.equal(calls, 2);
});

test('closes the preferences modal before Turbo caches the page', () => {
    const app = boot();
    app.click(app.dom.floating[0]);
    assert.equal(app.dom.modal.classList.contains('is-visible'), true);

    app.fire('turbo:before-cache');
    assert.equal(app.dom.modal.classList.contains('is-visible'), false);
});

test('the floating button is hidden until the visitor makes a choice', () => {
    const app = boot();
    const [button] = app.dom.floating;
    assert.equal(button.hasAttribute('hidden'), true);

    app.rejectButton.click();
    assert.equal(button.hasAttribute('hidden'), false);
});

test('the floating button is shown for returning visitors and opens the preferences', () => {
    const app = boot({
        cookies: fixtureCookies({
            '{preferences}': { json: { version: '1.0', status: 'accepted', age_days: 1, categories: { analytics: true } } },
        }),
    });
    const [button] = app.dom.floating;
    assert.equal(button.hasAttribute('hidden'), false);

    app.click(button);
    assert.equal(app.dom.modal.classList.contains('is-visible'), true);
    assert.equal(button.getAttribute('aria-expanded'), 'true');
    assert.equal(app.dom.toggles.find(t => t.dataset.category === 'analytics').checked, true);
    assert.equal(app.dom.toggles.find(t => t.dataset.category === 'marketing').checked, false);
});

test('the floating button is hidden again when the consent version changes', () => {
    const cookies = fixtureCookies({
        '{preferences}': { json: { version: '1.0', status: 'accepted', age_days: 1, categories: {} } },
    });
    const app = boot({ config: { consent_version: '2.0' }, cookies });

    assert.equal(app.dom.floating[0].hasAttribute('hidden'), true);
});

test('the toggle link class opens and closes the preferences', () => {
    const app = boot();
    const link = element(['showHideToggleCookiePreferencesModal']);

    assert.equal(app.click(link), true);
    assert.equal(app.dom.modal.classList.contains('is-visible'), true);
    app.click(link);
    assert.equal(app.dom.modal.classList.contains('is-visible'), false);
});

test('Consent Mode: accepting sends a granted update and a dataLayer event', () => {
    const app = boot({ config: { google_consent_mode: true } });
    app.acceptButton.click();

    const dataLayer = plain(app.window.dataLayer.map(entry => entry.event ? entry : Array.from(entry)));
    assert.deepEqual(dataLayer[0], ['consent', 'update', googleState({ necessary: true, analytics: true, marketing: true, preferences: false })]);
    assert.deepEqual(dataLayer[1], { event: 'cookie_consent_update', cookie_consent: { necessary: true, analytics: true, marketing: true } });
});

test('Consent Mode: rejecting denies everything except locked categories', () => {
    const app = boot({ config: { google_consent_mode: true } });
    app.rejectButton.click();

    assert.deepEqual(plain(Array.from(app.window.dataLayer[0])), ['consent', 'update', googleState({ necessary: true, analytics: false, marketing: false, preferences: false })]);
});

test('Consent Mode: saving preferences sends the chosen categories', () => {
    const app = boot({ config: { google_consent_mode: true } });
    app.toggles.find(t => t.dataset.category === 'analytics').checked = true;
    app.toggles.find(t => t.dataset.category === 'marketing').checked = false;
    app.saveButton.click();

    assert.deepEqual(plain(Array.from(app.window.dataLayer[0]))[2], googleState({ necessary: true, analytics: true, marketing: false, preferences: false }));
});

test('Consent Mode: uses an existing gtag function', () => {
    const app = boot({ config: { google_consent_mode: true } });
    const calls = [];
    app.window.gtag = (...args) => calls.push(args);
    app.acceptButton.click();

    assert.equal(calls.length, 1);
    assert.equal(calls[0][1], 'update');
});

test('Consent Mode: migrated legacy consent is sent to Google', () => {
    const app = boot({
        config: { google_consent_mode: true },
        cookies: fixtureCookies({ '{legacy}': { raw: 'accepted' }, '{legacy_preferences}': { raw: '{"analytics":true}' } }),
    });

    assert.deepEqual(plain(Array.from(app.window.dataLayer[0]))[2], googleState({ necessary: true, analytics: true, marketing: false, preferences: false }));
});

test('Consent Mode: nothing is sent when it is disabled', () => {
    const app = boot();
    app.acceptButton.click();

    assert.equal(app.window.dataLayer, undefined);
});

test('Consent Mode head snippet: optional settings', () => {
    const env = environment();
    env.window.cookieConsentGoogleConsentMode = {
        cookieName: 'x', version: '1.0', cookieLifetime: 365, rejectLifetime: 7, maxCookieLength: 4096,
        clockSkewTolerance: 86400, types: { analytics_storage: 'analytics' }, enabledCategories: ['analytics'],
        grantedCategories: [], waitForUpdate: 0, adsDataRedaction: false, urlPassthrough: true,
    };
    env.run(HEAD_SNIPPET);

    assert.deepEqual(plain(env.window.dataLayer.map(e => Array.from(e))), [
        ['consent', 'default', { analytics_storage: 'denied' }],
        ['set', 'url_passthrough', true],
    ]);
});

test('Consent Mode head snippet: does nothing without settings', () => {
    const env = environment();
    env.run(HEAD_SNIPPET);

    assert.equal(env.window.dataLayer, undefined);
});
