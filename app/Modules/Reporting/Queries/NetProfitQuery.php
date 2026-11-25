<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Queries;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\WastedMaterial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class NetProfitQuery
{
    /**
     * @return array{net_profit: float, gross_revenue: float, total_hpp: float, expenses_total: float, margin_rate: float, invoices: Collection<int, Invoice>, wasted_materials: Collection<int, WastedMaterial>, expenses: Collection<int, Expense>}
     */
    public function get(?string $branchId, string $startDate, string $endDate): array
    {
        $paymentQuery = Payment::query()
            ->with(['invoice.customer', 'branch'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        $this->scopeBranch($paymentQuery, $branchId);
        $grossRevenue = (float) (clone $paymentQuery)->sum('amount');

        $invoiceQuery = Invoice::query()
            ->with(['customer', 'branch'])
            ->whereBetween('created_date', [$startDate, $endDate]);

        $this->scopeBranch($invoiceQuery, $branchId);

        $invoices = (clone $invoiceQuery)->orderByDesc('created_date')->get();
        $invoiceCost = (float) (clone $invoiceQuery)->sum('total_cost');

        $wasteQuery = WastedMaterial::query()
            ->with(['material', 'branch', 'operator'])
            ->whereDate('logged_at', '>=', $startDate)
            ->whereDate('logged_at', '<=', $endDate);

        $this->scopeBranch($wasteQuery, $branchId);

        $wastedMaterials = $wasteQuery->orderByDesc('logged_at')->get();
        $wasteCost = $wastedMaterials->sum(
            fn (WastedMaterial $waste): float => (float) $waste->quantity * (float) ($waste->material ? $waste->material->cost : 0)
        );

        $expenseQuery = Expense::query()
            ->with('branch')
            ->whereBetween('incurred_at', [$startDate, $endDate]);

        $this->scopeBranch($expenseQuery, $branchId);

        $expenses = (clone $expenseQuery)->orderByDesc('incurred_at')->get();
        $expensesTotal = (float) (clone $expenseQuery)->sum('amount');

        $totalHpp = $invoiceCost + $wasteCost;
        $netProfit = $grossRevenue - $totalHpp - $expensesTotal;

        return [
            'net_profit' => $netProfit,
            'gross_revenue' => $grossRevenue,
            'total_hpp' => $totalHpp,
            'expenses_total' => $expensesTotal,
            'margin_rate' => $grossRevenue > 0 ? ($netProfit / $grossRevenue) * 100 : 0.0,
            'invoices' => $invoices,
            'wasted_materials' => $wastedMaterials,
            'expenses' => $expenses,
        ];
    }

    private function scopeBranch(Builder $query, ?string $branchId): void
    {
        if ($branchId !== null && $branchId !== '' && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }
    }
}
