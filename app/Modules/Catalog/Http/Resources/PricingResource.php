<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Models\Pricing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Pricing */
final class PricingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'product' => $this->product ? [
                'id' => $this->product->id,
                'name' => $this->product->name,
            ] : null,
            'material' => $this->material ? [
                'id' => $this->material->id,
                'name' => $this->material->name,
            ] : null,
            'customer_type' => $this->customer_type,
            'min_qty' => $this->min_qty,
            'price' => $this->price,
            'cost' => $this->cost,
            'profit' => $this->profit,
        ];
    }
}
