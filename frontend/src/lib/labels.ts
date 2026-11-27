import type { BadgeTone } from '../components/ui/Badge';

const roles: Record<string, string> = {
    owner: 'Pemilik',
    manager: 'Manajer',
    cashier: 'Kasir',
    designer: 'Desainer',
    production: 'Produksi',
    system: 'Sistem',
};

const roleTones: Record<string, BadgeTone> = {
    owner: 'brand',
    manager: 'info',
    cashier: 'success',
    designer: 'violet',
    production: 'warning',
    system: 'neutral',
};

const modelTypes: Record<string, string> = {
    ActivityLog: 'Jejak Audit',
    Attribute: 'Atribut',
    AttributePricing: 'Harga Atribut',
    AttributeType: 'Jenis Atribut',
    Backup: 'Backup',
    BackupSetting: 'Pengaturan Backup',
    Branch: 'Cabang',
    Customer: 'Pelanggan',
    Expense: 'Pengeluaran',
    Invoice: 'Invoice',
    Material: 'Material',
    MaterialBranchStock: 'Stok Bahan',
    Order: 'Pesanan',
    OrderStatusLog: 'Riwayat Status Pesanan',
    Payment: 'Pembayaran',
    Pricing: 'Harga',
    Product: 'Produk',
    ProductCategory: 'Kategori Produk',
    ProductCategoryDiscount: 'Diskon Kategori Produk',
    Purchase: 'Pembelian',
    PurchaseItem: 'Item Pembelian',
    StockLedger: 'Buku Stok',
    User: 'Pengguna',
    WastedMaterial: 'Bahan Terbuang',
};

const paymentMethods: Record<string, string> = {
    Cash: 'Tunai',
    Transfer: 'Transfer',
    QRIS: 'QRIS',
};

const attributeTypes: Record<string, string> = {
    finishing: 'Finishing',
    cut: 'Potong',
    display: 'Display',
};

const expenseTypes: Record<string, string> = {
    laba: 'Laba',
    modal: 'Modal',
};

const pricingTypes: Record<string, string> = {
    unit: 'Per Satuan',
    area: 'Per Area (m²)',
};
const ledgerTypes: Record<string, string> = {
    initial: 'Stok Awal',
    purchase: 'Pembelian',
    usage: 'Pemakaian',
    waste: 'Bahan Terbuang',
    adjustment: 'Koreksi',
    restock: 'Tambah Stok',
    usage_reversal: 'Pengembalian Pemakaian',
};

export function roleLabel(role: string): string {
    return roles[role] ?? role;
}

export function roleTone(role: string): BadgeTone {
    return roleTones[role] ?? 'neutral';
}

export function usernameLabel(username: string): string {
    return username === 'system' ? 'Sistem' : username;
}

export function modelTypeLabel(type: string | null): string {
    if (type === null) {
        return '-';
    }

    const basename = type.split('\\').pop() || type;

    return modelTypes[basename] ?? basename;
}

export function paymentMethodLabel(method: string): string {
    return paymentMethods[method] ?? method;
}

export function attributeTypeLabel(type: string): string {
    return attributeTypes[type] ?? type;
}

export function expenseTypeLabel(type: string): string {
    return expenseTypes[type] ?? type;
}

export function pricingTypeLabel(type: string): string {
    return pricingTypes[type] ?? type;
}

export function listCardTitle(title: string): string {
    if (title.startsWith('Daftar ')) {
        return title;
    }

    return `Daftar ${title.replace(/^Manajemen /, '')}`;
}
export function ledgerTypeLabel(type: string | null | undefined): string {
    if (!type) {
        return '-';
    }

    return ledgerTypes[type] ?? type;
}
