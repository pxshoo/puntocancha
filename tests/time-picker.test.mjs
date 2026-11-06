import test from 'node:test';
import assert from 'node:assert/strict';
import timePicker from '../resources/js/time-picker.js';

test('closing picker accepts midnight and forces zero minutes', () => {
    const picker = timePicker('23:30', true);
    picker.setHour(24);
    assert.equal(picker.time, '24:00');
    picker.setMinute(15);
    assert.equal(picker.time, '24:00');
    picker.setHour(20);
    assert.equal(picker.time, '20:00');
});

test('normal time picker rejects hour 24', () => {
    const picker = timePicker('08:00');
    picker.setHour(24);
    assert.equal(picker.time, '08:00');
});
