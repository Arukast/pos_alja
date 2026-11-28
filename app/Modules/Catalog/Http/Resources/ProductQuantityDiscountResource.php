<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Models\ProductQuantityDiscount;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductQuantityDiscount */
final class ProductQuantityDiscountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', fn (): ?array => $this->product === null ? null : [
                'id' => $this->product->id,
                'name' => $this->product->name,
            ]),
            'min_qty' => $this->min_qty,
            'discount_percent' => Decimal::money($this->discount_percent),
        ];
    }
}
