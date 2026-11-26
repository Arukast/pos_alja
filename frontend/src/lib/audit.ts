type Payload = Record<string, unknown>;

export interface AuditFieldRow {
    key: string;
    value: string;
}

export interface AuditComparisonRow {
    key: string;
    before: string;
    after: string;
}

export type AuditPayloadView =
    | { kind: 'empty' }
    | { kind: 'comparison'; rows: AuditComparisonRow[] }
    | { kind: 'fields'; rows: AuditFieldRow[] };

function isObject(value: unknown): value is Payload {
    return typeof value === 'object' && value !== null;
}

function displayValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '-';
    }

    return isObject(value) ? JSON.stringify(value) : String(value);
}

function valueAt(side: unknown, key: string): unknown {
    return isObject(side) ? side[key] : undefined;
}

/** Maps an audit payload to the comparison or key/value table the modal renders. */
export function auditPayloadView(payload: Payload | null): AuditPayloadView {
    if (!payload || Object.keys(payload).length === 0) {
        return { kind: 'empty' };
    }

    const keys = Object.keys(payload);
    const beforeAfterShape =
        keys.every((key) => key === 'before' || key === 'after') &&
        (isObject(payload.before) || isObject(payload.after));

    if (beforeAfterShape) {
        const union = new Set<string>();

        for (const side of [payload.before, payload.after]) {
            if (isObject(side)) {
                Object.keys(side).forEach((key) => union.add(key));
            }
        }

        return {
            kind: 'comparison',
            rows: [...union].map((key) => ({
                key,
                before: displayValue(valueAt(payload.before, key)),
                after: displayValue(valueAt(payload.after, key)),
            })),
        };
    }

    return {
        kind: 'fields',
        rows: keys.map((key) => ({ key, value: displayValue(payload[key]) })),
    };
}
