import type { Product } from '../../api/types';

/** Keeps a material only when the selected product still accepts it. */
export function materialForProduct(
    product: Pick<Product, 'requires_material' | 'material_ids'> | undefined,
    currentMaterialId: string | null
): string | null {
    if (!product?.requires_material) {
        return null;
    }

    const allowedIds = product.material_ids ?? [];

    return allowedIds.length === 0 || (currentMaterialId !== null && allowedIds.includes(currentMaterialId))
        ? currentMaterialId
        : null;
}

/** Keeps a saved material selectable on edit even when it is inactive or no longer compatible. */
export function materialOptionsForEdit(
    materials: readonly { id: string; name: string }[],
    allowedIds: readonly string[],
    savedMaterial: { id: string; name: string } | null
): Array<{ id: string; label: string }> {
    const options = materials
        .filter((material) => allowedIds.length === 0 || allowedIds.includes(material.id))
        .map((material) => ({ id: material.id, label: material.name }));

    if (savedMaterial === null || options.some((option) => option.id === savedMaterial.id)) {
        return options;
    }

    const suffix = materials.some((material) => material.id === savedMaterial.id)
        ? 'tidak kompatibel'
        : 'nonaktif';

    return [...options, { id: savedMaterial.id, label: `${savedMaterial.name} (${suffix})` }];
}
