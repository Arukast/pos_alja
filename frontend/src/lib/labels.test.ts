import { describe, expect, it } from 'vitest';

import {
    attributeTypeLabel,
    expenseTypeLabel,
    modelTypeLabel,
    paymentMethodLabel,
    paymentTypeLabel,
    ledgerTypeLabel,
    listCardTitle,
    roleLabel,
    roleTone,
    usernameLabel,
} from './labels';

describe('roleLabel', () => {
    it('labels every stored role', () => {
        expect(roleLabel('owner')).toBe('Pemilik');
        expect(roleLabel('manager')).toBe('Manajer');
        expect(roleLabel('cashier')).toBe('Kasir');
        expect(roleLabel('designer')).toBe('Desainer');
        expect(roleLabel('production')).toBe('Produksi');
        expect(roleLabel('system')).toBe('Sistem');
    });

    it('falls back to the input for an unknown role', () => {
        expect(roleLabel('auditor')).toBe('auditor');
    });
});

describe('roleTone', () => {
    it('maps every stored role to a badge tone', () => {
        expect(roleTone('owner')).toBe('brand');
        expect(roleTone('manager')).toBe('info');
        expect(roleTone('cashier')).toBe('success');
        expect(roleTone('designer')).toBe('violet');
        expect(roleTone('production')).toBe('warning');
        expect(roleTone('system')).toBe('neutral');
    });

    it('falls back to neutral for an unknown role', () => {
        expect(roleTone('auditor')).toBe('neutral');
    });
});

describe('listCardTitle', () => {
    it('prefixes a short list title', () => {
        expect(listCardTitle('Pembelian')).toBe('Daftar Pembelian');
        expect(listCardTitle('Bahan & Stok')).toBe('Daftar Bahan & Stok');
    });

    it('strips a leading Manajemen prefix', () => {
        expect(listCardTitle('Manajemen Pengguna')).toBe('Daftar Pengguna');
        expect(listCardTitle('Manajemen Produk')).toBe('Daftar Produk');
        expect(listCardTitle('Manajemen Cabang')).toBe('Daftar Cabang');
    });

    it('keeps an existing Daftar title unchanged', () => {
        expect(listCardTitle('Daftar Pesanan')).toBe('Daftar Pesanan');
    });

    it('only strips Manajemen at the start', () => {
        expect(listCardTitle('Bukan Manajemen Pengguna')).toBe(
            'Daftar Bukan Manajemen Pengguna'
        );
    });
});

describe('usernameLabel', () => {
    it('renders the system actor in Indonesian', () => {
        expect(usernameLabel('system')).toBe('Sistem');
    });

    it('keeps a human username', () => {
        expect(usernameLabel('owner-contoh')).toBe('owner-contoh');
    });
});

describe('modelTypeLabel', () => {
    it('maps known model classes to Indonesian nouns', () => {
        expect(modelTypeLabel('App\\Models\\BackupSetting')).toBe('Pengaturan Backup');
        expect(modelTypeLabel('App\\Models\\Order')).toBe('Pesanan');
        expect(modelTypeLabel('App\\Models\\Invoice')).toBe('Invoice');
        expect(modelTypeLabel('App\\Models\\Material')).toBe('Material');
    });

    it('falls back to the class basename for an unknown model', () => {
        expect(modelTypeLabel('App\\Models\\UnknownThing')).toBe('UnknownThing');
    });

    it('renders a dash for a missing model type', () => {
        expect(modelTypeLabel(null)).toBe('-');
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

describe('paymentTypeLabel', () => {
    it('labels a down payment as DP and anything else as Pelunasan', () => {
        expect(paymentTypeLabel('down_payment')).toBe('DP');
        expect(paymentTypeLabel('settlement')).toBe('Pelunasan');
        expect(paymentTypeLabel('')).toBe('Pelunasan');
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
        expect(ledgerTypeLabel('waste')).toBe('Bahan Terbuang');
        expect(ledgerTypeLabel('adjustment')).toBe('Koreksi');
        expect(ledgerTypeLabel('restock')).toBe('Tambah Stok');
        expect(ledgerTypeLabel('usage_reversal')).toBe('Pengembalian Pemakaian');
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
