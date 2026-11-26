<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\User;
use App\Support\Decimal;
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
        $actor = $request->user();

        if ($actor instanceof User && $actor->role !== 'owner') {
            $stocks = $stocks
                ->filter(fn (MaterialBranchStock $stock): bool => $stock->branch_id === $actor->branch_id)
                ->values();
        }

        $single = $stocks->count() === 1 ? $stocks->first() : null;
        $resolvedBranchId = $this->resource->getAttribute('resolved_stock_branch_id');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'dimensions' => $this->dimensions,
            'unit' => $this->unit,
            'cost' => Decimal::money($this->cost),
            'price' => Decimal::money($this->price),
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'is_active' => (bool) $this->category->is_active,
            ] : null,
            'current_stock' => $single?->current_stock,
            'min_stock' => $single?->min_stock,
            'is_low_stock' => $resolvedBranchId === 'all' || $single === null
                ? null
                : (float) $single->current_stock <= (float) $single->min_stock,
            'stocks' => $stocks->map(fn (MaterialBranchStock $stock): array => [
                'branch_id' => $stock->branch_id,
                'branch_name' => $stock->branch->name,
                'current_stock' => $stock->current_stock,
                'min_stock' => $stock->min_stock,
                'is_low_stock' => (float) $stock->current_stock <= (float) $stock->min_stock,
            ])->values(),
        ];
    }
}
