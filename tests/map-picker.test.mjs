import test from 'node:test';
import assert from 'node:assert/strict';
import clubLocation, { googleAddress, loadGoogleMaps } from '../resources/js/club-location.js';

test('Google Maps requires a key before contacting Google', async () => {
    await assert.rejects(loadGoogleMaps(''), /missing-key/);
});
test('Google address components populate street number and commune', () => {
    assert.deepEqual(googleAddress({ address_components: [
        { types: ['route'], long_name: 'Camino Nuevo' },
        { types: ['street_number'], long_name: '45' },
        { types: ['administrative_area_level_3'], long_name: 'Puerto Varas' },
    ] }), { address: 'Camino Nuevo 45', city: 'Puerto Varas' });
    assert.deepEqual(googleAddress(null), { address: '', city: '' });
});
test('selected point remains available for directions including zero coordinates', () => {
    const state = clubLocation('Calle 1', 'Puerto Montt');
    state.selectPoint({ latitude: 0, longitude: 0 });
    assert.equal(state.hasPoint, true);
    assert.ok(state.locationMapUrl.endsWith('0.0000000%2C0.0000000'));
    state.clearPoint();
    assert.equal(state.hasPoint, false);
    assert.ok(state.locationMapUrl.includes('Calle%201'));
});
test('unavailable geocoding preserves editable address and selected coordinates', async () => {
    const state = clubLocation('Direccion anterior', 'Puerto Montt', -41.39, -72.88);
    state.closeMap = () => {};
    await state.applyPoint();
    assert.equal(state.locationAddress, 'Direccion anterior');
    assert.equal(state.hasPoint, true);
    assert.ok(state.locationNotice.includes('manualmente'));
    assert.equal(state.resolving, false);
});
test('Google map click and geocoding update the selected location', async () => {
    const previousWindow = globalThis.window;
    const callbacks = {};
    const result = { address_components: [
        { types: ['route'], long_name: 'Camino Nuevo' },
        { types: ['street_number'], long_name: '45' },
        { types: ['locality'], long_name: 'Puerto Varas' },
    ] };
    class Map {
        addListener(event, callback) { callbacks[event] = callback; }
        setCenter() {}
        setZoom() {}
    }
    class Marker {
        constructor(options) { Object.assign(this, options); }
        addListener() {}
    }
    class Geocoder { async geocode() { return { results: [result] }; } }
    globalThis.window = { google: { maps: { importLibrary: async () => ({ Map, AdvancedMarkerElement: Marker, Geocoder }) } } };
    try {
        const state = clubLocation('Direccion anterior', 'Puerto Montt', -41.39, -72.88);
        state.$refs = { openMap: { dataset: { googleMapsKey: 'test' } }, mapCanvas: {} };
        state.$nextTick = async () => {};
        state.closeMap = () => {};
        await state.openMap();
        assert.equal(state.mapError, '');
        callbacks.click({ latLng: { lat: () => -41.32, lng: () => -72.98 } });
        await state.applyPoint();
        assert.equal(state.latitude, '-41.3200000');
        assert.equal(state.longitude, '-72.9800000');
        assert.equal(state.locationAddress, 'Camino Nuevo 45');
        assert.equal(state.locationCity, 'Puerto Varas');
    } finally { globalThis.window = previousWindow; }
});
