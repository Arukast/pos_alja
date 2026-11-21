<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Models\Expense;
use App\Models\User;
use App\Support\SafeBroadcast;

final class RecordExpense
{
    /**
     * @param  array{amount: numeric, type: string, incurred_at: string, description?: string|null, category?: string|null}  $data
     */
    public function execute(array $data, User $cashier): Expense
    {
        $expense = Expense::create([
            'branch_id' => $cashier->branch_id,
            'incurred_at' => $data['incurred_at'],
            'amount' => round((float) $data['amount'], 2),
            'description' => $data['description'] ?? null,
            'cashier_name' => $cashier->name,
            'type' => $data['type'],
            'category' => $data['category'] ?? 'Operational',
        ]);

        SafeBroadcast::orderUpdated($cashier->branch_id, $cashier);

        return $expense;
    }
}
