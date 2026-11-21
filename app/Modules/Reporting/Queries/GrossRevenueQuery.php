<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Queries;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class GrossRevenueQuery
{
    /**
     * @return array{grand_total: float, cash_total: float, transfer_total: float, qris_total: float, payments: Collection<int, Payment>}
     */
    public function get(?string $branchId, string $startDate, string $endDate): array
    {
        $query = Payment::query()
            ->with(['invoice.customer', 'branch'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        $this->scopeBranch($query, $branchId);

        $payments = (clone $query)->orderByDesc('created_at')->get();

        $cash = (float) (clone $query)->where('payment_method', 'Cash')->sum('amount');
        $transfer = (float) (clone $query)->where('payment_method', 'Transfer')->sum('amount');
        $qris = (float) (clone $query)->where('payment_method', 'QRIS')->sum('amount');

        return [
            'grand_total' => $cash + $transfer + $qris,
            'cash_total' => $cash,
            'transfer_total' => $transfer,
            'qris_total' => $qris,
            'payments' => $payments,
        ];
    }

    private function scopeBranch(Builder $query, ?string $branchId): void
    {
        if ($branchId !== null && $branchId !== '' && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }
    }
}
