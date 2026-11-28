export function formatQuantity(value: string | number | null | undefined): string {
    if (value === null || value === undefined) {
        return '-';
    }

    if (typeof value === 'string' && value.trim() === '') {
        return '-';
    }

    const numeric = typeof value === 'number' ? value : Number.parseFloat(value);

    if (!Number.isFinite(numeric)) {
        return '-';
    }

    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(numeric);
}

/** Short label for a quantity tier applied to a line; empty when none was applied. */
export function quantityDiscountLabel(value: string | number | null | undefined): string {
    if (value === null || value === undefined) {
        return '';
    }

    const numeric = typeof value === 'number' ? value : Number.parseFloat(value);

    if (!Number.isFinite(numeric) || numeric <= 0) {
        return '';
    }

    const percent = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(numeric);

    return `Diskon jumlah (${percent}%)`;
}

export function formatMoney(value: string | number): string {
    const numeric = typeof value === 'number' ? value : Number.parseFloat(value);

    if (!Number.isFinite(numeric)) {
        return 'Rp 0';
    }

    const rounded = Math.round(numeric * 100) / 100;
    const negative = rounded < 0;
    const absolute = Math.abs(rounded);
    const hasCents = Math.round((absolute % 1) * 100) !== 0;

    const formatted = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: hasCents ? 2 : 0,
        maximumFractionDigits: 2,
    }).format(absolute);

    return `${negative ? '-' : ''}Rp ${formatted}`;
}
