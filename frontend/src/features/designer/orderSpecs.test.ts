import { describe, expect, it } from 'vitest';

import { hasMissingLinearLength } from './orderSpecs';

describe('hasMissingLinearLength', () => {
    it('does not block unit or area orders', () => {
        expect(hasMissingLinearLength({
            length: null,
            area: '0.00',
            pricing: { product: 'Sticker', material: 'Vinyl', pricing_type: 'unit' },
        })).toBe(false);

        expect(hasMissingLinearLength({
            length: null,
            area: '3.00',
            pricing: { product: 'Banner', material: 'Flexy', pricing_type: 'area' },
        })).toBe(false);
    });
});
