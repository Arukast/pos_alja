import { describe, expect, it } from 'vitest';

import { editEligibility } from './editEligibility';

const order = (overrides: Partial<{
    status: number;
    product_id: string | null;
    is_manual_price: boolean;
}> = {}) => ({
    status: 0,
    product_id: '00000000-0000-4000-8000-000000000001',
    is_manual_price: false,
    ...overrides,
});

describe('editEligibility', () => {
    it('allows unpaid/DP invoices with pending priced orders', () => {
        expect(editEligibility({ status: 0, orders: [order()] }))
            .toEqual({ editable: true, reason: null });
        expect(editEligibility({ status: 1, orders: [order()] }))
            .toEqual({ editable: true, reason: null });
    });

    it('returns lunas before other locks', () => {
        expect(editEligibility({
            status: 2,
            orders: [order({ status: 1, is_manual_price: true })],
        })).toEqual({ editable: false, reason: 'lunas' });
    });

    it('returns sudah diproduksi for any non-pending order', () => {
        expect(editEligibility({ status: 1, orders: [order({ status: 3 })] }))
            .toEqual({ editable: false, reason: 'sudah diproduksi' });
    });

    it('returns tarif tidak tersedia for a null product_id', () => {
        expect(editEligibility({ status: 1, orders: [order({ product_id: null })] }))
            .toEqual({ editable: false, reason: 'tarif tidak tersedia' });
    });

    it('returns harga manual for a manual-price order', () => {
        expect(editEligibility({ status: 1, orders: [order({ is_manual_price: true })] }))
            .toEqual({ editable: false, reason: 'harga manual' });
    });

    it('stays editable when product identity is present in the payload', () => {
        expect(editEligibility({ status: 1, orders: [order()] }))
            .toEqual({ editable: true, reason: null });
    });
});
