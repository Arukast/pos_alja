<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Models\Attribute;
use App\Models\AttributeType;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductQuantityDiscount;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
final class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $materials = $this->relationLoaded('materials') ? $this->materials : collect();
        $attributeTypes = $this->relationLoaded('attributeTypes')
            ? $this->attributeTypes
            : collect();
        $compatibleAttributes = $this->relationLoaded('compatibleAttributes')
            ? $this->compatibleAttributes
            : collect();

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'selling_unit' => $this->selling_unit,
            'is_active' => (bool) $this->is_active,
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'is_active' => (bool) $this->category->is_active,
            ] : null,
            'requires_material' => (bool) $this->requires_material,
            'uses_attributes' => (bool) $this->uses_attributes,
            'attribute_type_ids' => $attributeTypes->pluck('id')->values()->all(),
            'attribute_types' => $attributeTypes->map(static fn (AttributeType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
            ])->values()->all(),
            'attribute_ids' => $compatibleAttributes->pluck('id')->values()->all(),
            'compatible_attributes' => $compatibleAttributes->map(static fn (Attribute $attribute): array => [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'type' => $attribute->type,
                'type_name' => $attribute->typeInfo->name ?? $attribute->type,
            ])->values()->all(),
            'materials' => $materials->map(static fn (Material $material): array => [
                'id' => $material->id,
                'name' => $material->name,
            ])->values()->all(),
            'material_ids' => $materials->pluck('id')->values()->all(),
        ];

        $user = $request->user();
        if ($user instanceof User && in_array($user->role, ['owner', 'manager', 'cashier', 'designer'], true)) {
            $data['price'] = Decimal::money($this->price);
        }
        if ($user instanceof User && in_array($user->role, ['owner', 'manager'], true)) {
            $data['cost'] = Decimal::money($this->cost);
        }
        if ($user instanceof User && in_array($user->role, ['owner', 'manager', 'designer'], true)) {
            $tiers = $this->relationLoaded('quantityDiscounts') ? $this->quantityDiscounts : collect();
            $data['tiers'] = $tiers->map(static fn (ProductQuantityDiscount $tier): array => [
                'id' => $tier->id,
                'min_qty' => (int) $tier->min_qty,
                'discount_percent' => Decimal::money($tier->discount_percent),
            ])->values()->all();
        }

        return $data;
    }
}
