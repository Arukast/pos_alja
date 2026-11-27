import { describe, expect, it } from 'vitest';

import type { Order } from '../../api/types';
import { hasMissingLinearLength, isOrderEditable } from './orderSpecs';

describe('hasMissingLinearLength', () => {
    it('does not block unit or area orders', () => {
        const sticker = {
            length: null,
            area: '0.00',
            product_id: 'p-sticker',
            material_id: 'm-vinyl',
            material: 'Vinyl',
            selling_unit: 'pcs',
        };
        const banner = {
            length: null,
            area: '3.00',
            product_id: 'p-banner',
            material_id: 'm-flexy',
            material: 'Flexy',
            selling_unit: 'm2',
        };

        expect(hasMissingLinearLength(sticker)).toBe(false);
        expect(hasMissingLinearLength(banner)).toBe(false);
    });
});

describe('isOrderEditable', () => {
    it('treats legacy orders with null material identity as non-editable', () => {
        const legacy = {
            status: 0,
            product_id: null,
            material_id: null,
        } as Order;

        expect(isOrderEditable(legacy)).toBe(false);
    });

    it('allows pending orders linked to a product', () => {
        const pending = {
            status: 0,
            product_id: 'p-banner',
            material_id: null,
        } as Order;

        expect(isOrderEditable(pending)).toBe(true);
    });
});
