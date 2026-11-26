const roles: Record<string, string> = {
    owner: 'Pemilik',
    manager: 'Manajer',
    cashier: 'Kasir',
    designer: 'Desainer',
    production: 'Produksi',
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
export function ledgerTypeLabel(type: string | null | undefined): string {
    if (!type) {
        return '-';
    }

    return ledgerTypes[type] ?? type;
}
