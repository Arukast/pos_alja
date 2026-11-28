<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Resources;

use App\Models\Invoice;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Invoice */
final class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $request->user()?->role;
        $showCost = in_array($role, ['owner', 'manager'], true);

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'branch' => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'address' => $this->branch->address,
                'phone' => $this->branch->phone,
            ] : null,
            'customer' => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
                'address' => $this->customer->address,
                'institution' => $this->institution ?? $this->customer->institution,
                'email' => $this->customer->email,
                'type' => $this->customer->type,
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
            'discount' => Decimal::money($this->discount),
            'dp_amount' => Decimal::money($this->dp_amount),
            'accounts_receivable' => Decimal::money($this->accounts_receivable),
            'grand_total' => Decimal::money($this->grand_total),
            'total_cost' => $this->when($showCost, fn (): string => Decimal::money($this->total_cost)),
            'total_profit' => $this->when($showCost, fn (): string => Decimal::money($this->total_profit)),
            'orders' => OrderResource::collection($this->whenLoaded('orders')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
