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
        materials:
            product.requires_material === false
                ? 'Tanpa bahan'
                : materials.length === 0
                  ? 'Semua bahan'
                  : materials.map((item) => item.name).join(', '),
        types:
            product.uses_attributes === false
                ? 'Tanpa tipe'
                : types.length === 0
                  ? 'Semua tipe'
                  : types.map((item) => item.name).join(', '),
        attributes:
            product.uses_attributes === false
                ? 'Tanpa atribut'
                : attributes.length === 0
                  ? 'Semua atribut'
                  : attributes.map((item) => item.name).join(', '),
    };
}
