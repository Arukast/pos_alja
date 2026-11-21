import { Badge, type BadgeTone } from './ui/Badge';

export function InvoiceStatusBadge({ status }: { status: number }) {
    const map: Record<number, { label: string; tone: BadgeTone }> = {
        0: { label: 'Unpaid', tone: 'warning' },
        1: { label: 'Down Payment', tone: 'info' },
        2: { label: 'Paid', tone: 'success' },
    };
    const value = map[status] ?? { label: 'Unknown', tone: 'neutral' as BadgeTone };

    return <Badge tone={value.tone}>{value.label}</Badge>;
}

export function PickupStatusBadge({ status }: { status: number }) {
    return status === 1 ? (
        <Badge tone="success">Picked up</Badge>
    ) : (
        <Badge tone="warning">Awaiting pickup</Badge>
    );
}

export function OrderStatusBadge({ status }: { status: number }) {
    const map: Record<number, { label: string; tone: BadgeTone }> = {
        0: { label: 'Queued', tone: 'warning' },
        1: { label: 'Processing', tone: 'info' },
        2: { label: 'Completed', tone: 'success' },
    };
    const value = map[status] ?? { label: 'Unknown', tone: 'neutral' as BadgeTone };

    return <Badge tone={value.tone}>{value.label}</Badge>;
}
