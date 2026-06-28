<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\WastedMaterial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    /**
     * Get aggregate and itemized gross revenue breakdown.
     *
     * @param  string|null  $branchId  'all' or UUID of the branch
     * @param  string  $startDate  Y-m-d format
     * @param  string  $endDate  Y-m-d format
     * @return array{
     *     grand_total: int,
     *     cash_total: int,
     *     transfer_total: int,
     *     qris_total: int,
     *     payments: Collection
     * }
     */
    public function getGrossRevenueBreakdown(?string $branchId, string $startDate, string $endDate): array
    {
        $query = Payment::query()
            ->with(['invoice.customer', 'branch'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $totalsQuery = clone $query;

        $payments = $query->orderBy('created_at', 'desc')->get();

        $cashTotal = (int) (clone $totalsQuery)->where('payment_method', 'Cash')->sum('amount');
        $transferTotal = (int) (clone $totalsQuery)->where('payment_method', 'Transfer')->sum('amount');
        $qrisTotal = (int) (clone $totalsQuery)->where('payment_method', 'QRIS')->sum('amount');
        $grandTotal = $cashTotal + $transferTotal + $qrisTotal;

        return [
            'grand_total' => $grandTotal,
            'cash_total' => $cashTotal,
            'transfer_total' => $transferTotal,
            'qris_total' => $qrisTotal,
            'payments' => $payments,
        ];
    }

    /**
     * Get aggregate and itemized base cost (HPP) breakdown.
     *
     * @param  string|null  $branchId  'all' or UUID of the branch
     * @param  string  $startDate  Y-m-d format
     * @param  string  $endDate  Y-m-d format
     * @return array{
     *     total_hpp: int,
     *     invoice_cost_total: int,
     *     wasted_cost_total: int,
     *     invoices: Collection,
     *     wasted_materials: Collection
     * }
     */
    public function getBaseCostBreakdown(?string $branchId, string $startDate, string $endDate): array
    {
        $invoiceQuery = Invoice::query()
            ->with(['customer', 'branch'])
            ->whereBetween('created_date', [$startDate, $endDate]);

        if ($branchId && $branchId !== 'all') {
            $invoiceQuery->where('branch_id', $branchId);
        }

        $wastedQuery = WastedMaterial::query()
            ->join('materials', 'wasted_materials.material_id', '=', 'materials.id')
            ->with(['material', 'branch', 'operator'])
            ->select('wasted_materials.*')
            ->whereDate('wasted_materials.logged_at', '>=', $startDate)
            ->whereDate('wasted_materials.logged_at', '<=', $endDate);

        if ($branchId && $branchId !== 'all') {
            $wastedQuery->where('wasted_materials.branch_id', $branchId);
        }

        $invoices = $invoiceQuery->orderBy('created_date', 'desc')->get();
        $wastedMaterials = $wastedQuery->orderBy('wasted_materials.logged_at', 'desc')->get();

        $invoiceCostTotal = (int) (clone $invoiceQuery)->sum('total_cost');
        $wastedCostTotal = (int) (clone $wastedQuery)->sum(DB::raw('wasted_materials.quantity * materials.cost'));
        $totalHpp = $invoiceCostTotal + $wastedCostTotal;

        return [
            'total_hpp' => $totalHpp,
            'invoice_cost_total' => $invoiceCostTotal,
            'wasted_cost_total' => $wastedCostTotal,
            'invoices' => $invoices,
            'wasted_materials' => $wastedMaterials,
        ];
    }

    /**
     * Get aggregate and itemized net profit breakdown.
     *
     * @param  string|null  $branchId  'all' or UUID of the branch
     * @param  string  $startDate  Y-m-d format
     * @param  string  $endDate  Y-m-d format
     * @return array{
     *     net_profit: int,
     *     gross_revenue: int,
     *     total_hpp: int,
     *     expenses_total: int,
     *     margin_rate: float,
     *     invoices: Collection,
     *     wasted_materials: Collection,
     *     expenses: Collection
     * }
     */
    public function getNetProfitBreakdown(?string $branchId, string $startDate, string $endDate): array
    {
        // 1. Gross Revenue (Payments)
        $paymentQuery = Payment::query()
            ->with(['invoice.customer', 'branch'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        if ($branchId && $branchId !== 'all') {
            $paymentQuery->where('branch_id', $branchId);
        }

        $grossRevenue = (int) (clone $paymentQuery)->sum('amount');

        // 2. Base Cost (HPP)
        $invoiceQuery = Invoice::query()
            ->with(['customer', 'branch'])
            ->whereBetween('created_date', [$startDate, $endDate]);

        if ($branchId && $branchId !== 'all') {
            $invoiceQuery->where('branch_id', $branchId);
        }

        $wastedQuery = WastedMaterial::query()
            ->join('materials', 'wasted_materials.material_id', '=', 'materials.id')
            ->with(['material', 'branch', 'operator'])
            ->select('wasted_materials.*')
            ->whereDate('wasted_materials.logged_at', '>=', $startDate)
            ->whereDate('wasted_materials.logged_at', '<=', $endDate);

        if ($branchId && $branchId !== 'all') {
            $wastedQuery->where('wasted_materials.branch_id', $branchId);
        }

        $invoices = $invoiceQuery->orderBy('created_date', 'desc')->get();
        $wastedMaterials = $wastedQuery->orderBy('wasted_materials.logged_at', 'desc')->get();

        $invoiceCostTotal = (int) (clone $invoiceQuery)->sum('total_cost');
        $wastedCostTotal = (int) (clone $wastedQuery)->sum(DB::raw('wasted_materials.quantity * materials.cost'));
        $totalHpp = $invoiceCostTotal + $wastedCostTotal;

        // 3. Operational Expenses
        $expenseQuery = Expense::query()
            ->with(['branch'])
            ->whereBetween('incurred_at', [$startDate, $endDate]);

        if ($branchId && $branchId !== 'all') {
            $expenseQuery->where('branch_id', $branchId);
        }

        $expenses = $expenseQuery->orderBy('incurred_at', 'desc')->get();
        $expensesTotal = (int) (clone $expenseQuery)->sum('amount');

        // 4. Calculations
        $netProfit = $grossRevenue - $totalHpp - $expensesTotal;
        $marginRate = $grossRevenue > 0 ? ($netProfit / $grossRevenue) * 100 : 0.0;

        return [
            'net_profit' => $netProfit,
            'gross_revenue' => $grossRevenue,
            'total_hpp' => $totalHpp,
            'expenses_total' => $expensesTotal,
            'margin_rate' => $marginRate,
            'invoices' => $invoices,
            'wasted_materials' => $wastedMaterials,
            'expenses' => $expenses,
        ];
    }
}
