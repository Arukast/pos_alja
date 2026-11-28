import { describe, expect, it } from 'vitest';

import type { Order } from '../../api/types';
import { isOrderEditable } from './orderSpecs';

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
