export type Period = 'daily' | 'weekly' | 'monthly';

export interface DateRange {
    start: string;
    end: string;
}

function toIsoDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function parseDate(value: string): Date | null {
    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value);

    if (!match) {
        return null;
    }

    return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
}

export function periodRange(period: Period, reference: Date = new Date()): DateRange {
    const date = new Date(reference.getFullYear(), reference.getMonth(), reference.getDate());

    if (period === 'daily') {
        return { start: toIsoDate(date), end: toIsoDate(date) };
    }

    if (period === 'weekly') {
        const day = date.getDay();
        const offset = day === 0 ? -6 : 1 - day;
        const start = new Date(date);
        start.setDate(date.getDate() + offset);
        const end = new Date(start);
        end.setDate(start.getDate() + 6);

        return { start: toIsoDate(start), end: toIsoDate(end) };
    }

    const start = new Date(date.getFullYear(), date.getMonth(), 1);
    const end = new Date(date.getFullYear(), date.getMonth() + 1, 0);

    return { start: toIsoDate(start), end: toIsoDate(end) };
}

export function formatDate(value: string | Date): string {
    const date = typeof value === 'string' ? parseDate(value) : value;

    if (!date || Number.isNaN(date.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(date);
}

export function todayIso(): string {
    return toIsoDate(new Date());
}
