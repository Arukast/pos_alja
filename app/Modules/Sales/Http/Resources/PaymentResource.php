<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Resources;

use App\Models\Payment;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payment */
final class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => Decimal::money($this->amount),
            'payment_method' => $this->payment_method,
            'type' => $this->type,
            'cashier_name' => $this->cashier_name,
            'created_at' => $this->created_at,
        ];
    }
}
