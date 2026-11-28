import { describe, expect, it } from 'vitest';

import type { Product } from '../../api/types';
import { materialForProduct, materialOptionsForEdit } from './materialOptions';

type MaterialConfig = Pick<Product, 'requires_material' | 'material_ids'>;

const product = (overrides: Partial<MaterialConfig> = {}): MaterialConfig => ({
    requires_material: true,
    material_ids: ['m-vinyl'],
    ...overrides,
});

describe('materialForProduct', () => {
    it('keeps a material that the newly selected product accepts', () => {
        expect(materialForProduct(product(), 'm-vinyl')).toBe('m-vinyl');
    });

    it('clears a material that the newly selected product does not accept', () => {
        expect(materialForProduct(product(), 'm-kertas')).toBeNull();
    });

    it('keeps any material when the product has no compatibility filter', () => {
        expect(materialForProduct(product({ material_ids: [] }), 'm-kertas')).toBe('m-kertas');
    });

    it('clears the material for a product that does not use materials', () => {
        expect(materialForProduct(product({ requires_material: false }), 'm-vinyl')).toBeNull();
    });
});

describe('materialOptionsForEdit', () => {
    const materials = [
        { id: 'm-vinyl', name: 'Vinyl' },
        { id: 'm-kertas', name: 'Kertas' },
    ];

    it('keeps compatible active materials unchanged', () => {
        expect(materialOptionsForEdit(materials, ['m-vinyl'], null)).toEqual([
            { id: 'm-vinyl', label: 'Vinyl' },
        ]);
    });

    it('keeps a saved material excluded from the product compatibility list', () => {
        expect(materialOptionsForEdit(materials, ['m-vinyl'], { id: 'm-kertas', name: 'Kertas' })).toEqual([
            { id: 'm-vinyl', label: 'Vinyl' },
            { id: 'm-kertas', label: 'Kertas (tidak kompatibel)' },
        ]);
    });

    it('keeps a saved material missing from the active options', () => {
        expect(materialOptionsForEdit(materials, [], { id: 'm-lama', name: 'Bahan Lama' })).toEqual([
            { id: 'm-vinyl', label: 'Vinyl' },
            { id: 'm-kertas', label: 'Kertas' },
            { id: 'm-lama', label: 'Bahan Lama (nonaktif)' },
        ]);
    });

    it('does not duplicate a saved material that is already offered', () => {
        expect(materialOptionsForEdit(materials, [], { id: 'm-vinyl', name: 'Vinyl' })).toHaveLength(2);
    });
});
