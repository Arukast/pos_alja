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
    linear_dimension: 'Dimensi Linear',
    tiered_volume: 'Volume Bertingkat',
};

const attributeTypes: Record<string, string> = {
    finishing: 'Finishing',
    cut: 'Potong',
    display: 'Display',
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
