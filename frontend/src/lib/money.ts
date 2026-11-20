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
