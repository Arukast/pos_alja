<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Purchase */
final class PurchaseResource extends JsonResource
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
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null,
            'purchased_at' => $this->purchased_at,
            'supplier' => $this->supplier,
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'items' => $this->items
                ->map(fn (PurchaseItem $item): array => [
                    'id' => $item->id,
                    'material' => $item->material ? [
                        'id' => $item->material->id,
                        'name' => $item->material->name,
                    ] : null,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'subtotal' => $item->subtotal,
                ])
                ->values()
                ->all(),
            'created_at' => $this->created_at,
        ];
    }
}
