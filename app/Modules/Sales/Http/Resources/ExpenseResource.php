<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Resources;

use App\Models\Expense;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Expense */
final class ExpenseResource extends JsonResource
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
            'incurred_at' => $this->incurred_at,
            'amount' => Decimal::money($this->amount),
            'description' => $this->description,
            'cashier_name' => $this->cashier_name,
            'type' => $this->type,
            'category' => $this->category,
        ];
    }
}
