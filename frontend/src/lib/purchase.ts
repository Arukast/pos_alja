const DECIMAL_PATTERN = /^(-?)(\d+)(?:\.(\d{1,2}))?$/;

function toCents(value: string): bigint {
    const match = DECIMAL_PATTERN.exec(value);

    if (match === null) {
        throw new Error(`Invalid decimal value [${value}].`);
    }

    const cents = BigInt(match[2]) * 100n + BigInt((match[3] ?? '').padEnd(2, '0'));

    return match[1] === '-' ? -cents : cents;
}

function formatCents(cents: bigint): string {
    const absolute = cents < 0n ? -cents : cents;
    const fraction = (absolute % 100n).toString().padStart(2, '0');

    return `${cents < 0n ? '-' : ''}${absolute / 100n}.${fraction}`;
}

function lineSubtotalCents(quantity: string, unitCost: string): bigint {
    const product = toCents(quantity) * toCents(unitCost);
    const absolute = product < 0n ? -product : product;
    const rounded = (absolute + 50n) / 100n;

    return product < 0n ? -rounded : rounded;
}

export function purchaseLineSubtotal(quantity: string, unitCost: string): string {
    return formatCents(lineSubtotalCents(quantity, unitCost));
}

export function purchaseTotal(lines: Array<{ quantity: string; unit_cost: string }>): string {
    const total = lines.reduce(
        (sum, line) => sum + lineSubtotalCents(line.quantity, line.unit_cost),
        0n,
    );

    return formatCents(total);
}
