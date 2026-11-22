<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Models\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockLedger */
final class StockLedgerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'branch' => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ] : null,
            'material' => $this->material ? [
                'id' => $this->material->id,
                'name' => $this->material->name,
            ] : null,
            'logged_at' => $this->logged_at,
            'initial_stock' => $this->initial_stock,
            'added_stock' => $this->added_stock,
            'used_stock' => $this->used_stock,
            'wasted_stock' => $this->wasted_stock,
            'remaining_stock' => $this->remaining_stock,
            'notes' => $this->notes,
        ];
    }
}
