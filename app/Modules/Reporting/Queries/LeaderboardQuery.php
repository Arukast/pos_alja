<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Queries;

use App\Models\Invoice;
use App\Models\User;
use App\Models\WastedMaterial;
use App\Support\Decimal;

final class LeaderboardQuery
{
    /**
     * @return array{leaders: array<int, array<string, mixed>>}
     */
    public function forManager(User $actor): array
    {
        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $designers = User::query()
            ->where('role', 'designer')
            ->where('branch_id', $actor->branch_id)
            ->orderBy('name')
            ->get();

        $invoices = Invoice::query()
            ->where('branch_id', $actor->branch_id)
            ->whereBetween('created_date', [$start, $end])
            ->get(['designer_id', 'grand_total']);

        $wasteCounts = WastedMaterial::query()
            ->where('branch_id', $actor->branch_id)
            ->whereDate('logged_at', '>=', $start)
            ->whereDate('logged_at', '<=', $end)
            ->get(['designer_id'])
            ->countBy('designer_id');

        $leaders = $designers->map(function (User $designer) use ($invoices, $wasteCounts): array {
            $rows = $invoices->where('designer_id', $designer->id);
            $orderCount = $rows->count();
            $revenue = (float) $rows->sum('grand_total');

            return [
                'id' => $designer->id,
                'name' => $designer->name,
                'order_count' => $orderCount,
                'revenue' => Decimal::money($revenue),
                'average_order_value' => Decimal::money($orderCount > 0 ? $revenue / $orderCount : 0),
                'waste_count' => (int) ($wasteCounts[$designer->id] ?? 0),
            ];
        })->sortByDesc('revenue')->values()->all();

        return ['leaders' => $leaders];
    }
}
