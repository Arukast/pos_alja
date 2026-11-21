<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Queries;

use App\Models\Invoice;
use App\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class SparklineQuery
{
    /**
     * @return array{points: array<int, float>, labels: array<int, string>}
     */
    public function get(?string $branchId, string $period): array
    {
        $points = [];
        $labels = [];

        if ($period === 'weekly') {
            for ($index = 6; $index >= 0; $index--) {
                $date = now()->subDays($index);
                $points[] = $this->profitBetween(
                    $date->copy()->startOfDay(),
                    $date->copy()->endOfDay(),
                    $branchId
                );
                $labels[] = $date->locale('id')->isoFormat('ddd');
            }

            return ['points' => $points, 'labels' => $labels];
        }

        if ($period === 'monthly') {
            for ($index = 3; $index >= 0; $index--) {
                $week = now()->subWeeks($index);
                $points[] = $this->profitBetween(
                    $week->copy()->startOfWeek()->startOfDay(),
                    $week->copy()->endOfWeek()->endOfDay(),
                    $branchId
                );
                $labels[] = $index === 0 ? 'Minggu Ini' : $index.' mgg lalu';
            }

            return ['points' => $points, 'labels' => $labels];
        }

        for ($index = 0; $index < 6; $index++) {
            $start = now()->startOfDay()->addHours($index * 4);
            $end = now()->startOfDay()->addHours(($index + 1) * 4)->subSecond();

            $points[] = $this->profitBetween($start, $end, $branchId);
        }

        return [
            'points' => $points,
            'labels' => [
                '00:00-04:00',
                '04:00-08:00',
                '08:00-12:00',
                '12:00-16:00',
                '16:00-20:00',
                '20:00-24:00',
            ],
        ];
    }

    private function profitBetween(CarbonInterface $start, CarbonInterface $end, ?string $branchId): float
    {
        $paymentQuery = Payment::query()->whereBetween('created_at', [$start, $end]);
        $invoiceQuery = Invoice::query()->whereBetween('created_date', [$start, $end]);

        $this->scopeBranch($paymentQuery, $branchId);
        $this->scopeBranch($invoiceQuery, $branchId);

        return (float) (clone $paymentQuery)->sum('amount')
            - (float) (clone $invoiceQuery)->sum('total_cost');
    }

    private function scopeBranch(Builder $query, ?string $branchId): void
    {
        if ($branchId !== null && $branchId !== '' && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }
    }
}
