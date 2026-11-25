import { describe, expect, it } from 'vitest';

import { purchaseLineSubtotal, purchaseTotal } from './purchase';

describe('purchaseLineSubtotal', () => {
    it('multiplies quantity by unit cost and rounds to cents', () => {
        expect(purchaseLineSubtotal('2.5', '333.33')).toBe('833.33');
        expect(purchaseLineSubtotal('1', '1')).toBe('1.00');
    });

    it('rounds a half cent up', () => {
        expect(purchaseLineSubtotal('0.05', '0.10')).toBe('0.01');
    });

    it('handles the smallest allowed price', () => {
        expect(purchaseLineSubtotal('0.10', '0.20')).toBe('0.02');
    });

    it('keeps precision at the maximum allowed values', () => {
        expect(purchaseLineSubtotal('999999.99', '9999999.99')).toBe('9999999890000.00');
    });

    it('formats negative values with two places', () => {
        expect(purchaseLineSubtotal('-2.5', '333.33')).toBe('-833.33');
    });

    it('throws on malformed input', () => {
        expect(() => purchaseLineSubtotal('2.555', '1')).toThrow('Invalid decimal value [2.555].');
        expect(() => purchaseLineSubtotal('1', 'abc')).toThrow('Invalid decimal value [abc].');
        expect(() => purchaseLineSubtotal('', '1')).toThrow('Invalid decimal value [].');
    });
});

describe('purchaseTotal', () => {
    it('sums the rounded line subtotals', () => {
        const lines = [
            { quantity: '2.5', unit_cost: '333.33' },
            { quantity: '2.5', unit_cost: '333.33' },
        ];

        expect(purchaseTotal(lines)).toBe('1666.66');
    });

    it('rounds each line before summing so the total matches the displayed lines', () => {
        const lines = [
            { quantity: '0.05', unit_cost: '0.10' },
            { quantity: '0.05', unit_cost: '0.10' },
        ];

        expect(purchaseTotal(lines)).toBe('0.02');
    });

    it('returns zero for an empty cart', () => {
        expect(purchaseTotal([])).toBe('0.00');
    });

    it('throws on a malformed line', () => {
        expect(() => purchaseTotal([{ quantity: '2.5', unit_cost: 'abc' }])).toThrow(
            'Invalid decimal value [abc].',
        );
    });
});
