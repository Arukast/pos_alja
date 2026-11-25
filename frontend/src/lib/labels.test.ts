import { describe, expect, it } from 'vitest';

import {
    archetypeLabel,
    attributeTypeLabel,
    expenseTypeLabel,
    ledgerTypeLabel,
    paymentMethodLabel,
    roleLabel,
} from './labels';

describe('roleLabel', () => {
    it('labels every stored role', () => {
        expect(roleLabel('owner')).toBe('Pemilik');
        expect(roleLabel('manager')).toBe('Manajer');
        expect(roleLabel('cashier')).toBe('Kasir');
        expect(roleLabel('designer')).toBe('Desainer');
        expect(roleLabel('production')).toBe('Produksi');
    });

    it('falls back to the input for an unknown role', () => {
        expect(roleLabel('auditor')).toBe('auditor');
    });
});

describe('paymentMethodLabel', () => {
    it('labels every stored payment method', () => {
        expect(paymentMethodLabel('Cash')).toBe('Tunai');
        expect(paymentMethodLabel('Transfer')).toBe('Transfer');
        expect(paymentMethodLabel('QRIS')).toBe('QRIS');
    });

    it('falls back to the input for an unknown method', () => {
        expect(paymentMethodLabel('Cheque')).toBe('Cheque');
    });
});

describe('archetypeLabel', () => {
    it('labels every stored archetype', () => {
        expect(archetypeLabel('per_piece')).toBe('Per Potong');
        expect(archetypeLabel('surface_area')).toBe('Per Meter Persegi');
        expect(archetypeLabel('linear_dimension')).toBe('Per Meter Lari');
        expect(archetypeLabel('tiered_volume')).toBe('Harga Bertingkat');
    });

    it('falls back to the input for an unknown archetype', () => {
        expect(archetypeLabel('flat_rate')).toBe('flat_rate');
    });
});

describe('expenseTypeLabel', () => {
    it('labels every stored expense type', () => {
        expect(expenseTypeLabel('laba')).toBe('Laba');
        expect(expenseTypeLabel('modal')).toBe('Modal');
    });

    it('falls back to the input for an unknown type', () => {
        expect(expenseTypeLabel('operational')).toBe('operational');
    });
});

describe('attributeTypeLabel', () => {
    it('labels every stored attribute type', () => {
        expect(attributeTypeLabel('finishing')).toBe('Finishing');
        expect(attributeTypeLabel('cut')).toBe('Potong');
        expect(attributeTypeLabel('display')).toBe('Display');
    });

    it('falls back to the input for an unknown type', () => {
        expect(attributeTypeLabel('packaging')).toBe('packaging');
    });
});

describe('ledgerTypeLabel', () => {
    it('labels every stored ledger type', () => {
        expect(ledgerTypeLabel('initial')).toBe('Stok Awal');
        expect(ledgerTypeLabel('purchase')).toBe('Pembelian');
        expect(ledgerTypeLabel('usage')).toBe('Pemakaian');
        expect(ledgerTypeLabel('waste')).toBe('Sisa Bahan');
        expect(ledgerTypeLabel('adjustment')).toBe('Koreksi');
        expect(ledgerTypeLabel('restock')).toBe('Tambah Stok');
    });

    it('falls back to the input for an unknown type', () => {
        expect(ledgerTypeLabel('transfer')).toBe('transfer');
    });

    it('renders a dash for a missing type', () => {
        expect(ledgerTypeLabel(null)).toBe('-');
        expect(ledgerTypeLabel(undefined)).toBe('-');
        expect(ledgerTypeLabel('')).toBe('-');
    });
});
