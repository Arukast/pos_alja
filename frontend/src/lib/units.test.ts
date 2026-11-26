import { describe, expect, it } from 'vitest';

import { ledgerQuantity, quantityWithUnit, unitLabel, unitOptions, wasteByUnitLabel } from './units';

describe('unitOptions', () => {
    it('lists the seven stored units in a fixed order', () => {
        expect(unitOptions.map((option) => option.value)).toEqual([
            'm2',
            'pcs',
            'lembar',
            'roll',
            'kg',
            'liter',
            'batang',
        ]);
    });
});

describe('unitLabel', () => {
    it('renders square metres with a superscript', () => {
        expect(unitLabel('m2')).toBe('m²');
    });

    it('renders every other stored unit unchanged', () => {
        expect(unitLabel('pcs')).toBe('pcs');
        expect(unitLabel('lembar')).toBe('lembar');
        expect(unitLabel('roll')).toBe('roll');
        expect(unitLabel('kg')).toBe('kg');
        expect(unitLabel('liter')).toBe('liter');
        expect(unitLabel('batang')).toBe('batang');
    });

    it('falls back to pcs for a missing or unknown unit', () => {
        expect(unitLabel(null)).toBe('pcs');
        expect(unitLabel(undefined)).toBe('pcs');
        expect(unitLabel('sheet')).toBe('pcs');
    });
});

describe('quantityWithUnit', () => {
    it('appends the display label to a formatted quantity', () => {
        expect(quantityWithUnit('2', 'lembar')).toBe('2 lembar');
        expect(quantityWithUnit('1.5', 'm2')).toBe('1,5 m²');
    });

    it('keeps the dash for missing or invalid quantities', () => {
        expect(quantityWithUnit(null, 'm2')).toBe('-');
        expect(quantityWithUnit(undefined, 'pcs')).toBe('-');
        expect(quantityWithUnit('', 'kg')).toBe('-');
        expect(quantityWithUnit('not-a-number', 'roll')).toBe('-');
    });
});

describe('ledgerQuantity', () => {
    it('renders a zero movement as a dash', () => {
        expect(ledgerQuantity('0.00', 'lembar')).toBe('-');
        expect(ledgerQuantity(0, 'm2')).toBe('-');
    });

    it('renders nonzero movements like quantityWithUnit', () => {
        expect(ledgerQuantity('2.50', 'm2')).toBe('2,5 m²');
        expect(ledgerQuantity(null, 'kg')).toBe('-');
    });
});

describe('wasteByUnitLabel', () => {
    it('joins one entry per unit in order', () => {
        expect(
            wasteByUnitLabel([
                { unit: 'lembar', quantity: 2 },
                { unit: 'm2', quantity: 1.5 },
            ]),
        ).toBe('2 lembar · 1,5 m²');
    });

    it('renders a dash when there is no waste', () => {
        expect(wasteByUnitLabel([])).toBe('-');
    });
});
