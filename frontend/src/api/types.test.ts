import { describe, expect, it } from 'vitest';

import type { Quote } from './queries';
import type { Order } from './types';
import { CUSTOMER_INSTITUTIONS } from './types';

describe('CUSTOMER_INSTITUTIONS', () => {
    it('test_customer_institution_constants_match_backend', () => {
        expect(CUSTOMER_INSTITUTIONS).toEqual([
            'Masyarakat/Retail',
            'Sekolah',
            'Universitas',
            'Pemerintahan',
            'Bisnis/Perusahaan',
            'Organisasi/Yayasan',
        ]);
        expect(CUSTOMER_INSTITUTIONS).toContain('Bisnis/Perusahaan');
        expect(CUSTOMER_INSTITUTIONS).toContain('Organisasi/Yayasan');
        expect(CUSTOMER_INSTITUTIONS[0]).toBe('Masyarakat/Retail');
    });
});

describe('quantity discount contract', () => {
    it('types the published quote fields', () => {
        const quote = {
            unit_price: '25000.00',
            area: 6,
            quantity: 10,
            subtotal: '2250000.00',
            quantity_discount_percent: '5.00',
            discount_amount: '125000.00',
        } satisfies Quote;

        expect(quote.quantity_discount_percent).toBe('5.00');
        expect(quote.discount_amount).toBe('125000.00');
    });

    it('types the published order fields', () => {
        const order = {
            id: 'order-1',
            product_id: 'p-banner',
            material_id: 'm-flexy',
            material: 'Flexy',
            customer_type: 'Reguler',
            quantity_discount_percent: '5.00',
            discount_amount: '125000.00',
            file_name: null,
            notes: null,
            area: '6.00',
            width: '2',
            length: '3',
            quantity: '10',
            selected_attributes: [],
            ordered_at: '2026-11-27',
            status: 0,
            pickup_status: 0,
            is_manual_price: false,
        } satisfies Order;

        expect(order.quantity_discount_percent).toBe('5.00');
        expect(order.discount_amount).toBe('125000.00');
    });
});
