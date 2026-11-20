<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Models\Material;
use App\Models\MaterialBranchStock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Material */
final class MaterialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $stocks = $this->branchStocks;
        $single = $stocks->count() === 1 ? $stocks->first() : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'dimensions' => $this->dimensions,
            'cost' => $this->cost,
            'price' => $this->price,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'current_stock' => $single?->current_stock,
            'min_stock' => $single?->min_stock,
            'stocks' => $stocks->map(fn (MaterialBranchStock $stock): array => [
                'branch_id' => $stock->branch_id,
                'branch_name' => $stock->branch->name,
                'current_stock' => $stock->current_stock,
                'min_stock' => $stock->min_stock,
            ])->values(),
        ];
    }
}
