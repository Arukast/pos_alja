<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Support;

use App\Models\Attribute;
use App\Models\AttributeType;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

final class ProductComposition
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function validate(array $data, ?Product $product = null): void
    {
        $existingTypeIds = $product?->attributeTypes()->pluck('attribute_type_product.attribute_type_id')->all() ?? [];
        $existingAttributeIds = $product?->compatibleAttributes()->pluck('attribute_product.attribute_id')->all() ?? [];

        $finalTypeIds = array_key_exists('attribute_type_ids', $data)
            ? array_values($data['attribute_type_ids'])
            : $existingTypeIds;

        foreach (array_values($data['attribute_type_ids'] ?? []) as $typeId) {
            if (in_array($typeId, $existingTypeIds, true)) {
                continue;
            }

            if (! AttributeType::query()->whereKey($typeId)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    'attribute_type_ids' => 'Tipe atribut baru tidak aktif.',
                ]);
            }
        }

        foreach (array_values($data['attribute_ids'] ?? []) as $attributeId) {
            if (in_array($attributeId, $existingAttributeIds, true)) {
                continue;
            }

            $attribute = Attribute::query()->with('typeInfo')->find($attributeId);
            $type = $attribute?->typeInfo;

            if ($attribute === null
                || ! (bool) $attribute->is_active
                || $type === null
                || ! (bool) $type->is_active
                || ($finalTypeIds !== [] && ! in_array($type->id, $finalTypeIds, true))
            ) {
                throw ValidationException::withMessages([
                    'attribute_ids' => 'Atribut baru harus aktif dan berada dalam tipe yang diizinkan.',
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sync(Product $product, array $data): void
    {
        if (array_key_exists('material_ids', $data)) {
            $product->materials()->sync(array_values($data['material_ids']));
        }

        if (array_key_exists('attribute_type_ids', $data)) {
            $product->attributeTypes()->sync(array_values($data['attribute_type_ids']));
        }

        if (array_key_exists('attribute_ids', $data)) {
            $product->compatibleAttributes()->sync(array_values($data['attribute_ids']));
        }
    }
}
