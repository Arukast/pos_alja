<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Models\ProductCategory;
use App\Models\ProductCategoryDiscount;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductCategory */
final class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'calculation_archetype' => $this->calculation_archetype,
            'allowed_attribute_types' => $this->allowed_attribute_types ?? [],
            'discount_tiers' => $this->relationLoaded('discountTiers')
                ? $this->discountTiers->map(fn (ProductCategoryDiscount $tier): array => [
                    'id' => $tier->id,
                    'min_qty' => (int) $tier->min_qty,
                    'discount_percent' => Decimal::money($tier->discount_percent),
                ])->values()->all()
                : [],
        ];
    }
}
