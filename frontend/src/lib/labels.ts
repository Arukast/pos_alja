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

const archetypes: Record<string, string> = {
    per_piece: 'Per Potong',
    surface_area: 'Per Meter Persegi',
    linear_dimension: 'Per Meter Lari',
    tiered_volume: 'Harga Bertingkat',
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

const ledgerTypes: Record<string, string> = {
    initial: 'Stok Awal',
    purchase: 'Pembelian',
    usage: 'Pemakaian',
    waste: 'Sisa Bahan',
    adjustment: 'Koreksi',
    restock: 'Tambah Stok',
};

export function roleLabel(role: string): string {
    return roles[role] ?? role;
}

export function paymentMethodLabel(method: string): string {
    return paymentMethods[method] ?? method;
}

export function archetypeLabel(archetype: string): string {
    return archetypes[archetype] ?? archetype;
}

export function attributeTypeLabel(type: string): string {
    return attributeTypes[type] ?? type;
}

export function expenseTypeLabel(type: string): string {
    return expenseTypes[type] ?? type;
}

export function ledgerTypeLabel(type: string | null | undefined): string {
    if (!type) {
        return '-';
    }

    return ledgerTypes[type] ?? type;
}

/** Maps production status codes to Indonesian labels and their allowed next state. */
export function productionStatusTransition(
    status: number
): { label: string; nextStatus: number | null } {
    const transitions: Record<number, { label: string; nextStatus: number | null }> = {
        0: { label: 'Antre', nextStatus: 1 },
        1: { label: 'Diproses', nextStatus: 2 },
        2: { label: 'Selesai', nextStatus: null },
    };

    return transitions[status] ?? { label: 'Tidak diketahui', nextStatus: null };
}
