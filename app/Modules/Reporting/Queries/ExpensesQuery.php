<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Queries;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Collection;

final class ExpensesQuery
{
    /**
     * @return array{expenses_total: float, by_category: array<string, float>, expenses: Collection<int, Expense>}
     */
    public function get(?string $branchId, string $startDate, string $endDate): array
    {
        $query = Expense::query()
            ->with('branch')
            ->whereBetween('incurred_at', [$startDate, $endDate]);

        if ($branchId !== null && $branchId !== '' && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $expenses = $query->orderByDesc('incurred_at')->get();
        $total = (float) $expenses->sum('amount');

        $byCategory = $expenses
            ->groupBy('category')
            ->map(fn (Collection $rows): float => (float) $rows->sum('amount'))
            ->toArray();

        return [
            'expenses_total' => $total,
            'by_category' => $byCategory,
            'expenses' => $expenses,
        ];
    }
}
