<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Models\WastedMaterial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WastedMaterial */
final class WasteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch' => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ] : null,
            'material' => $this->material ? [
                'id' => $this->material->id,
                'name' => $this->material->name,
                'unit' => $this->material->unit,
            ] : null,
            'order_id' => $this->order_id,
            'designer' => $this->designer ? [
                'id' => $this->designer->id,
                'name' => $this->designer->name,
            ] : null,
            'operator' => $this->operator ? [
                'id' => $this->operator->id,
                'name' => $this->operator->name,
            ] : null,
            'quantity' => $this->quantity,
            'dimensions' => $this->dimensions,
            'notes' => $this->notes,
            'logged_at' => $this->logged_at,
        ];
    }
}
