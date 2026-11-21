<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
final class ProductionOrderResource extends JsonResource
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
        ];
    }
}
