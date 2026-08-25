import { beforeEach, describe, expect, test } from 'vitest';
import { readOrderType, writeOrderType } from '../orderTypeStorage';

describe('orderTypeStorage', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    test('readOrderType returns null when unset or invalid', () => {
        expect(readOrderType()).toBeNull();

        localStorage.setItem('pebbles.orderType', 'courier');
        expect(readOrderType()).toBeNull();
    });

    test('writeOrderType persists valid fulfillment types', () => {
        writeOrderType('delivery');
        expect(readOrderType()).toBe('delivery');

        writeOrderType('takeaway');
        expect(readOrderType()).toBe('takeaway');

        writeOrderType('dine_in');
        expect(readOrderType()).toBe('dine_in');
    });

    test('writeOrderType clears storage for invalid values', () => {
        writeOrderType('takeaway');
        writeOrderType('nope');

        expect(localStorage.getItem('pebbles.orderType')).toBeNull();
        expect(readOrderType()).toBeNull();
    });
});
