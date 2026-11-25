import type { WasteByUnit } from '../api/types';
import { formatQuantity } from './money';

const unitLabels: Record<string, string> = {
    m2: 'm²',
    pcs: 'pcs',
    lembar: 'lembar',
    roll: 'roll',
    kg: 'kg',
    liter: 'liter',
    batang: 'batang',
};

export function unitLabel(unit: string | null | undefined): string {
    return unit && Object.hasOwn(unitLabels, unit) ? unitLabels[unit] : 'pcs';
}

export function quantityWithUnit(
    value: string | number | null | undefined,
    unit: string | null | undefined,
): string {
    const quantity = formatQuantity(value);

    return quantity === '-' ? '-' : `${quantity} ${unitLabel(unit)}`;
}

export function wasteByUnitLabel(entries: WasteByUnit[]): string {
    return entries.map((entry) => quantityWithUnit(entry.quantity, entry.unit)).join(' · ') || '-';
}
