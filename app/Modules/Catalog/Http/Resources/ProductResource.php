<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Models\Product;
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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'pricing_type' => $this->pricing_type,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'calculation_archetype' => $this->category->calculation_archetype,
            ] : null,
        ];
    }
}
