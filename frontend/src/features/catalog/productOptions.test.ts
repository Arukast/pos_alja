import { describe, expect, it } from 'vitest';

import type { Product } from '../../api/types';
import { productCompositionLabels } from './productOptions';

const base: Product = {
    id: 'p1',
    name: 'Banner',
    pricing_type: 'unit',
    is_active: true,
    category: null,
    materials: [],
    material_ids: [],
    requires_material: true,
    uses_attributes: true,
    attribute_types: [],
    attribute_type_ids: [],
    compatible_attributes: [],
    attribute_ids: [],
};

describe('productCompositionLabels', () => {
    it('uses Semua labels for empty pivots', () => {
        expect(productCompositionLabels(base)).toEqual({
            materials: 'Semua bahan',
            types: 'Semua tipe',
            attributes: 'Semua atribut',
        });
    });

    it('joins loaded names', () => {
        expect(
            productCompositionLabels({
                ...base,
                materials: [{ id: 'm1', name: 'Vinyl' }],
                attribute_types: [{ id: 't1', name: 'Finishing' }],
                compatible_attributes: [
                    { id: 'a1', name: 'Glossy', type: 'finishing', type_name: 'Finishing' },
                ],
            })
        ).toEqual({
            materials: 'Vinyl',
            types: 'Finishing',
            attributes: 'Glossy',
        });
    });
});
