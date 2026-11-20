<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Resources;

use App\Models\Order;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
final class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $request->user()?->role;
        $showSell = in_array($role, ['owner', 'manager', 'designer'], true);
        $showCost = in_array($role, ['owner', 'manager'], true);

        return [
            'id' => $this->id,
            'pricing_id' => $this->pricing_id,
            'file_name' => $this->file_name,
            'notes' => $this->notes,
            'area' => $this->area,
            'quantity' => $this->quantity,
            'unit_price' => $this->when($showSell, fn (): string => Decimal::money($this->unit_price)),
            'subtotal' => $this->when($showSell, fn (): string => Decimal::money($this->subtotal)),
            'cost' => $this->when($showCost, fn (): string => Decimal::money($this->cost)),
            'profit' => $this->when($showCost, fn (): string => Decimal::money($this->profit)),
            'ordered_at' => $this->ordered_at,
            'status' => $this->status,
            'pickup_status' => $this->pickup_status,
            'is_manual_price' => (bool) $this->is_manual_price,
        ];
    }
}
