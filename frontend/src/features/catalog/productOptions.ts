import type { Product } from '../../api/types';

export function productCompositionLabels(product: Product): {
    materials: string;
    types: string;
    attributes: string;
} {
    const materials = product.materials ?? [];
    const types = product.attribute_types ?? [];
    const attributes = product.compatible_attributes ?? [];

    return {
        materials: materials.length === 0 ? 'Semua bahan' : materials.map((item) => item.name).join(', '),
        types: types.length === 0 ? 'Semua tipe' : types.map((item) => item.name).join(', '),
        attributes:
            attributes.length === 0
                ? 'Semua atribut'
                : attributes.map((item) => item.name).join(', '),
    };
}
