import { Badge, type BadgeTone } from './ui/Badge';

export function InvoiceStatusBadge({ status }: { status: number }) {
    const map: Record<number, { label: string; tone: BadgeTone }> = {
        0: { label: 'Belum Dibayar', tone: 'warning' },
        1: { label: 'DP', tone: 'info' },
        2: { label: 'Lunas', tone: 'success' },
    };
    const value = map[status] ?? { label: 'Tidak Diketahui', tone: 'neutral' as BadgeTone };

    return <Badge tone={value.tone}>{value.label}</Badge>;
}

export function PickupStatusBadge({ status }: { status: number }) {
    return status === 1 ? (
        <Badge tone="success">Sudah Diambil</Badge>
    ) : (
        <Badge tone="warning">Belum Diambil</Badge>
    );
}

export function OrderStatusBadge({ status }: { status: number }) {
    const map: Record<number, { label: string; tone: BadgeTone }> = {
        0: { label: 'Antrean', tone: 'warning' },
        1: { label: 'Diproses', tone: 'info' },
        2: { label: 'Selesai', tone: 'success' },
        3: { label: 'Gagal', tone: 'danger' },
    };
    const value = map[status] ?? { label: 'Tidak Diketahui', tone: 'neutral' as BadgeTone };

    return <Badge tone={value.tone}>{value.label}</Badge>;
}
