<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Resources;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Invoice */
final class ProductionInvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'branch' => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ] : null,
            'customer' => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
                'address' => $this->customer->address,
            ] : null,
            'designer' => $this->designer ? [
                'id' => $this->designer->id,
                'name' => $this->designer->name,
            ] : null,
            'created_date' => $this->created_date,
            'deadline' => $this->deadline,
            'status' => $this->status,
            'pickup_status' => $this->pickup_status,
            'pickup_date' => $this->pickup_date,
            'source' => $this->source,
            'orders' => ProductionOrderResource::collection($this->whenLoaded('orders')),
        ];
    }
}
