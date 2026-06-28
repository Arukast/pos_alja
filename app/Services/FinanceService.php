<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Collection;

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
}
