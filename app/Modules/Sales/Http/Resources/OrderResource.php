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
            'product_id' => $this->product_id,
            'product' => $this->when($this->relationLoaded('product'),
                fn (): ?string => $this->product?->name),
            'material_id' => $this->material_id,
            'material' => $this->when($this->relationLoaded('material'),
                fn (): ?string => $this->material?->name),
            'customer_type' => $this->customer_type,
            'quantity_discount_percent' => $this->when(
                $showSell,
                fn (): string => Decimal::money($this->quantity_discount_percent)
            ),
            'discount_amount' => $this->when($showSell, fn (): string => Decimal::money($this->discount_amount)),
            'file_name' => $this->file_name,
            'notes' => $this->notes,
            'area' => $this->area,
            'width' => $this->width,
            'length' => $this->length,
            'quantity' => $this->quantity,
            'selected_attributes' => $this->selected_attributes ?? [],
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
