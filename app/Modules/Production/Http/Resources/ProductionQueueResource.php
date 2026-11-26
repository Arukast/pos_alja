<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
final class ProductionQueueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'notes' => $this->notes,
            'area' => $this->area,
            'quantity' => $this->quantity,
            'ordered_at' => $this->ordered_at,
            'status' => $this->status,
            'pickup_status' => $this->pickup_status,
            'has_waste' => (bool) ($this->wasted_materials_exists ?? $this->wastedMaterials()->exists()),
            'invoice' => $this->invoice ? [
                'id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'deadline' => $this->invoice->deadline,
                'pickup_status' => $this->invoice->pickup_status,
                'customer' => $this->invoice->customer ? [
                    'id' => $this->invoice->customer->id,
                    'name' => $this->invoice->customer->name,
                ] : null,
                'designer' => $this->invoice->designer ? [
                    'id' => $this->invoice->designer->id,
                    'name' => $this->invoice->designer->name,
                ] : null,
            ] : null,
            'operator' => $this->operator ? [
                'id' => $this->operator->id,
                'name' => $this->operator->name,
            ] : null,
        ];
    }
}
