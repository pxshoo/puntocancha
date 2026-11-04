import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { initInterfaceMotion } from '../resources/js/interface-motion.js';

const source = readFileSync(new URL('../resources/views/components/page-loading-head.blade.php', import.meta.url), 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1];

function loadingPage() {
    const classes = new Set();
    const attributes = new Map();
    const events = new Map();
    const timers = new Map();
    let id = 0;
    const root = {
        classList: { add: (...names) => names.forEach(name => classes.add(name)), remove: (...names) => names.forEach(name => classes.delete(name)) },
        setAttribute: (key, value) => attributes.set(key, value),
        removeAttribute: key => attributes.delete(key),
    };
    const context = {
        document: { documentElement: root, addEventListener: (name, fn) => events.set(name, fn) },
        window: { addEventListener: (name, fn) => events.set(name, fn) },
        setTimeout: (fn, delay) => { timers.set(++id, { fn, delay }); return id; },
        clearTimeout: key => timers.delete(key),
    };
    vm.runInNewContext(source, context);
    return { classes, attributes, events, timers, run: delay => {
        const entry = [...timers.entries()].find(([, timer]) => timer.delay === delay);
        assert.ok(entry, 'Expected timer exists');
        timers.delete(entry[0]);
        entry[1].fn();
    }};
}

test('reload shows branded loader and releases page after DOM is ready', () => {
    const page = loadingPage();
    assert.ok(page.classes.has('pc-loading'));
    assert.equal(page.attributes.get('aria-busy'), 'true');
    page.events.get('DOMContentLoaded')();
    assert.equal(page.attributes.has('aria-busy'), false);
    assert.ok(page.classes.has('pc-loading-leave'));
    page.run(200);
    assert.equal(page.classes.has('pc-loading'), false);
    assert.equal([...page.timers.values()].some(timer => timer.delay === 4000), false);
});

test('watchdog releases page if deferred JavaScript or DOM readiness stalls', () => {
    const page = loadingPage();
    page.run(4000);
    page.run(200);
    assert.equal(page.classes.has('pc-loading'), false);
    assert.equal(page.attributes.has('aria-busy'), false);
});

test('history restoration and duplicate lifecycle events cannot leave loader stuck', () => {
    const page = loadingPage();
    page.events.get('pageshow')();
    page.events.get('DOMContentLoaded')();
    page.run(200);
    assert.equal(page.classes.size, 0);
    assert.equal(page.timers.size, 0);
});

test('entrance animation respects reduced motion', () => {
    const previousWindow = globalThis.window;
    const previousDocument = globalThis.document;
    let observed = false;
    try {
        globalThis.document = { querySelectorAll: () => [{}] };
        globalThis.window = { matchMedia: () => ({ matches: true }), IntersectionObserver: class {} };
        globalThis.IntersectionObserver = class { constructor() { observed = true; } };
        initInterfaceMotion();
        assert.equal(observed, false);
    } finally {
        globalThis.window = previousWindow;
        globalThis.document = previousDocument;
        delete globalThis.IntersectionObserver;
    }
});
