import { describe, expect, it } from 'vitest';

import { formatMoney } from './money';

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
