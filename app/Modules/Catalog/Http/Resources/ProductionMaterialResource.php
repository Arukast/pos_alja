<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Material */
final class ProductionMaterialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $stock = $this->branchStocks->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'dimensions' => $this->dimensions,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'current_stock' => $stock?->current_stock,
            'min_stock' => $stock?->min_stock,
            'is_low_stock' => $stock !== null
                ? (float) $stock->current_stock <= (float) $stock->min_stock
                : null,
        ];
    }
}
