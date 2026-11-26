<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Models\Material;
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
        $materials = $this->relationLoaded('materials') ? $this->materials : collect();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'pricing_type' => $this->pricing_type,
            'is_active' => (bool) $this->is_active,
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'materials' => $materials->map(static fn (Material $material): array => [
                'id' => $material->id,
                'name' => $material->name,
            ])->values()->all(),
            'material_ids' => $materials->pluck('id')->values()->all(),
        ];
    }
}
