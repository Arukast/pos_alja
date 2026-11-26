import { describe, expect, it } from 'vitest';

import { hasMissingLinearLength } from './orderSpecs';

describe('hasMissingLinearLength', () => {
    it('blocks a linear order that has no stored length or area', () => {
        expect(hasMissingLinearLength({
            length: null,
            area: '0.00',
            pricing: { product: 'Banner', material: 'Flexy', calculation_archetype: 'linear_dimension' },
        })).toBe(true);
    });

    it('keeps a linear order with a stored length editable', () => {
        expect(hasMissingLinearLength({
            length: '2.50',
            area: '0.00',
            pricing: { product: 'Banner', material: 'Flexy', calculation_archetype: 'linear_dimension' },
        })).toBe(false);
    });

    it('does not block archetypes that do not use length', () => {
        expect(hasMissingLinearLength({
            length: null,
            area: '0.00',
            pricing: { product: 'Sticker', material: 'Vinyl', calculation_archetype: 'tiered_volume' },
        })).toBe(false);

        expect(hasMissingLinearLength({
            length: null,
            area: '3.00',
            pricing: { product: 'Banner', material: 'Flexy', calculation_archetype: 'surface_area' },
        })).toBe(false);
    });
});
