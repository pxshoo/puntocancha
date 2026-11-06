import test from 'node:test';
import assert from 'node:assert/strict';
import accessStatus from '../resources/js/access-status.js';

test('manual payment check explains pending approval without granting access', async () => {
    const previousFetch = globalThis.fetch;
    globalThis.fetch = async () => ({ ok: true, headers: new Headers({ 'content-type': 'application/json' }),
        json: async () => ({ enabled: false, status: 'manual_review', redirect: null }) });
    try {
        const status = accessStatus('/payment/result', true);
        await status.check(true);
        assert.ok(status.notice.includes('pendiente de aprobación'));
        assert.equal(status.verified, false);
        assert.equal(status.checking, false);
    } finally { globalThis.fetch = previousFetch; }
});

test('unexpected HTML response displays an error instead of silently ignoring the click', async () => {
    const previousFetch = globalThis.fetch;
    globalThis.fetch = async () => ({ ok: true, headers: new Headers({ 'content-type': 'text/html' }) });
    try {
        const status = accessStatus('/payment/result', true);
        await status.check(true);
        assert.ok(status.notice.includes('No pudimos consultar'));
        assert.equal(status.checking, false);
    } finally { globalThis.fetch = previousFetch; }
});

test('approved payment shows verification before entering club setup', async () => {
    const previousFetch = globalThis.fetch, previousWindow = globalThis.window, previousTimeout = globalThis.setTimeout;
    let redirected = null, continueSetup = null;
    globalThis.window = { location: { origin: 'https://app.test', assign: url => { redirected = url; } } };
    globalThis.fetch = async () => ({ ok: true, headers: new Headers({ 'content-type': 'application/json' }),
        json: async () => ({ enabled: true, status: 'paid', redirect: 'https://app.test/onboarding/paso-1' }) });
    globalThis.setTimeout = callback => { continueSetup = callback; return 1; };
    try {
        const status = accessStatus('/payment/result', true);
        await status.check();
        assert.equal(status.verified, true);
        assert.ok(status.notice.includes('Pago verificado'));
        assert.equal(redirected, null);
        continueSetup();
        assert.equal(redirected, 'https://app.test/onboarding/paso-1');
    } finally {
        globalThis.fetch = previousFetch; globalThis.window = previousWindow; globalThis.setTimeout = previousTimeout;
    }
});

test('reactivated session enters dashboard but suspended session stays on screen', async () => {
    const previousFetch = globalThis.fetch, previousWindow = globalThis.window;
    let enabled = false, redirected = null;
    globalThis.window = { location: { origin: 'https://app.test', assign: url => { redirected = url; } } };
    globalThis.fetch = async () => ({ ok: true, status: 200, headers: new Headers({ 'content-type': 'application/json' }),
        json: async () => ({ enabled, redirect: enabled ? 'https://app.test/dashboard' : null }) });
    try {
        const status = accessStatus('/cuenta/estado');
        await status.check();
        assert.equal(redirected, null);
        enabled = true;
        await status.check();
        assert.equal(redirected, 'https://app.test/dashboard');
    } finally { globalThis.fetch = previousFetch; globalThis.window = previousWindow; }
});

test('expired sessions show login message without attempting automatic authentication', async () => {
    const previousFetch = globalThis.fetch;
    globalThis.fetch = async () => ({ status: 401 });
    try {
        const status = accessStatus('/cuenta/estado');
        await status.check();
        assert.ok(status.notice.includes('sesión venció'));
        assert.equal(status.checking, false);
    } finally { globalThis.fetch = previousFetch; }
});
