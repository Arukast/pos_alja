export type EditLockReason =
    | 'lunas'
    | 'sudah diproduksi'
    | 'tarif tidak tersedia'
    | 'harga manual';

export interface EditEligibilityInput {
    status: number;
    orders: Array<{
        status: number;
        pricing_id: string | null;
        is_manual_price?: boolean;
    }> | null | undefined;
}

export interface EditEligibility {
    editable: boolean;
    reason: EditLockReason | null;
}

export function editEligibility(invoice: EditEligibilityInput): EditEligibility {
    const orders = invoice.orders ?? [];

    if (invoice.status === 2) {
        return { editable: false, reason: 'lunas' };
    }

    if (orders.some((order) => order.status !== 0)) {
        return { editable: false, reason: 'sudah diproduksi' };
    }

    if (orders.some((order) => order.pricing_id === null)) {
        return { editable: false, reason: 'tarif tidak tersedia' };
    }

    if (orders.some((order) => order.is_manual_price === true)) {
        return { editable: false, reason: 'harga manual' };
    }

    return { editable: true, reason: null };
}
