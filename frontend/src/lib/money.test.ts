import { describe, expect, it } from 'vitest';

import { formatMoney, formatQuantity, quantityDiscountLabel } from './money';

describe('formatMoney', () => {
    it('formats whole rupiah values', () => {
        expect(formatMoney('125000.00')).toBe('Rp 125.000');
        expect(formatMoney(0)).toBe('Rp 0');
    });

    it('keeps two decimals when cents are present', () => {
        expect(formatMoney('125000.50')).toBe('Rp 125.000,50');
    });

    it('formats negative values', () => {
        expect(formatMoney(-1500)).toBe('-Rp 1.500');
    });

    it('falls back to zero for invalid input', () => {
        expect(formatMoney('not-a-number')).toBe('Rp 0');
    });
});

describe('formatQuantity', () => {
    it('formats numeric strings with id-ID grouping', () => {
        expect(formatQuantity('2')).toBe('2');
        expect(formatQuantity('1250.5')).toBe('1.250,5');
        expect(formatQuantity('0.00')).toBe('0');
    });

    it('keeps at most two decimals', () => {
        expect(formatQuantity('12.345')).toBe('12,35');
    });

    it('formats numbers', () => {
        expect(formatQuantity(0)).toBe('0');
        expect(formatQuantity(1500)).toBe('1.500');
    });

    it('falls back to a dash for empty or invalid input', () => {
        expect(formatQuantity(null)).toBe('-');
        expect(formatQuantity(undefined)).toBe('-');
        expect(formatQuantity('')).toBe('-');
        expect(formatQuantity('   ')).toBe('-');
        expect(formatQuantity('not-a-number')).toBe('-');
        expect(formatQuantity(Number.NaN)).toBe('-');
        expect(formatQuantity(Number.POSITIVE_INFINITY)).toBe('-');
    });
});

describe('quantityDiscountLabel', () => {
    it('names the applied quantity discount without trailing zeros', () => {
        expect(quantityDiscountLabel('5.00')).toBe('Diskon jumlah (5%)');
        expect(quantityDiscountLabel('12.50')).toBe('Diskon jumlah (12,5%)');
        expect(quantityDiscountLabel(7)).toBe('Diskon jumlah (7%)');
    });

    it('stays empty when no discount was applied', () => {
        expect(quantityDiscountLabel('0.00')).toBe('');
        expect(quantityDiscountLabel(undefined)).toBe('');
    });
});
