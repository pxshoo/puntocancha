import { test } from 'node:test';
import assert from 'node:assert/strict';
import location from '../resources/js/club-location.js';

test('address search uses the typed city and encodes special characters', () => {
    const state = location('  Avenida Los Álamos 123 & Local 2  ', 'Puerto Montt');
    const url = new URL(state.locationMapUrl);
    assert.equal(url.origin, 'https://www.google.com');
    assert.equal(url.searchParams.get('api'), '1');
    assert.equal(url.searchParams.get('query'), 'Avenida Los Álamos 123 & Local 2, Puerto Montt, Chile');
});

test('changing address and city updates map search without saving', () => {
    const state = location('Calle Sur 1', 'Puerto Montt');
    state.syncLocation({ target: { name: 'address', value: 'Camino Lago 456' } });
    state.syncLocation({ target: { name: 'city', value: 'Puerto Varas' } });
    assert.equal(new URL(state.locationMapUrl).searchParams.get('query'), 'Camino Lago 456, Puerto Varas, Chile');
    state.syncLocation({ target: { name: 'phone', value: '12345678' } });
    assert.equal(state.locationCity, 'Puerto Varas');
});

test('incomplete locations do not generate a misleading map destination', () => {
    const state = location('', 'Puerto Montt');
    assert.equal(state.locationReady, false);
    assert.equal(state.locationMapUrl, null);
    state.locationAddress = 'Calle 123';
    state.locationCity = '   ';
    assert.equal(state.locationMapUrl, null);
});
