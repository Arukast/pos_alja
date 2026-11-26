<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Models\Expense;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Decimal;
use App\Support\SafeBroadcast;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class RecordExpense
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{amount: numeric, type: string, incurred_at: string, description?: string|null, category?: string|null}  $data
     */
    public function execute(array $data, User $cashier): Expense
    {
        $expense = DB::transaction(function () use ($data, $cashier): Expense {
            $expense = Expense::create([
                'branch_id' => $cashier->branch_id,
                'incurred_at' => $data['incurred_at'],
                'amount' => round((float) $data['amount'], 2),
                'description' => $data['description'] ?? null,
                'cashier_name' => $cashier->name,
                'type' => $data['type'],
                'category' => $data['category'] ?? 'Operasional',
            ]);

            $this->audit->record(
                $cashier,
                'CREATE',
                'Mencatat pengeluaran',
                $expense,
                [
                    'amount' => Decimal::money($expense->amount),
                    'type' => $expense->type,
                    'category' => $expense->category,
                    'incurred_at' => Carbon::parse($data['incurred_at'])->toDateString(),
                    'description' => $expense->description,
                ],
            );

            return $expense;
        });

        SafeBroadcast::orderUpdated($cashier->branch_id, $cashier);

        return $expense;
    }
}
