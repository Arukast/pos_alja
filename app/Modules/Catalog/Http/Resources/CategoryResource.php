<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Models\ProductCategory;
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
        ];
    }
}
