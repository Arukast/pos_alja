<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Queries;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Pricing;
use App\Models\StockLedger;
use App\Models\User;
use App\Modules\Catalog\Http\Resources\StockLedgerResource;
use App\Modules\Catalog\Queries\MaterialQueries;
use App\Modules\Production\Http\Resources\ProductionQueueResource;
use App\Modules\Production\Queries\ProductionQueue;
use App\Modules\Production\Queries\WasteByUnit;
use App\Modules\Sales\Http\Resources\ExpenseResource;
use App\Modules\Sales\Http\Resources\InvoiceResource;
use App\Support\Decimal;

final class DashboardQuery
{
    public function __construct(
        private readonly NetProfitQuery $netProfit,
        private readonly SparklineQuery $sparkline,
        private readonly LeaderboardQuery $leaderboard,
        private readonly ProductionQueue $productionQueue,
        private readonly WasteByUnit $wasteByUnit,
        private readonly MaterialQueries $materials,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(User $actor, string $period = 'daily', string $branchId = 'all'): array
    {
        return match ($actor->role) {
            'owner' => $this->owner($period, $branchId),
            'manager' => $this->manager($actor, $period),
            'cashier' => $this->cashier($actor),
            'designer' => $this->designer($actor),
            'production' => $this->production($actor),
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function owner(string $period, string $branchId): array
    {
        $period = in_array($period, ['daily', 'weekly', 'monthly'], true) ? $period : 'daily';

        $range = $this->range($period);
        $finance = $this->netProfit->get($branchId, $range['start'], $range['end']);
        $orders = $this->orderSummary($branchId, $range['start'], $range['end']);

        return [
            'role' => 'owner',
            'period' => $period,
            'finance' => [
                'gross_revenue' => Decimal::money($finance['gross_revenue']),
                'total_cost' => Decimal::money($finance['total_hpp']),
                'net_profit' => Decimal::money($finance['net_profit']),
                'expenses' => Decimal::money($finance['expenses_total']),
                'receivables' => $this->receivables($branchId),
            ],
            'order_count' => $orders['order_count'],
            'production' => $orders['production'],
            'sparkline' => $this->sparkline->get($branchId, $period),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function manager(User $actor, string $period): array
    {
        $period = in_array($period, ['daily', 'weekly', 'monthly'], true) ? $period : 'daily';
        $branchId = $actor->branch_id;
        $range = $this->range($period);

        $finance = $branchId === null
            ? ['gross_revenue' => 0.0, 'expenses_total' => 0.0]
            : $this->netProfit->get($branchId, $range['start'], $range['end']);

        $orders = $branchId === null
            ? [
                'order_count' => 0,
                'production' => [
                    'queued' => 0,
                    'processing' => 0,
                    'completed' => 0,
                    'failed' => 0,
                ],
            ]
            : $this->orderSummary($branchId, $range['start'], $range['end']);

        $counts = [
            'materials' => $this->materials->availableCount($actor, null),
            'staff' => $branchId === null ? 0 : User::query()
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->whereIn('role', ['cashier', 'designer', 'production'])
                ->count(),
            'pricing' => $branchId === null ? 0 : Pricing::query()
                ->where('branch_id', $branchId)
                ->count(),
        ];

        $stockLedgers = $branchId === null
            ? StockLedger::query()->whereRaw('1 = 0')->get()
            : StockLedger::query()
                ->with(['material', 'branch', 'user'])
                ->where('branch_id', $branchId)
                ->orderByDesc('logged_at')
                ->limit(8)
                ->get();

        $expenses = $branchId === null
            ? Expense::query()->whereRaw('1 = 0')->get()
            : Expense::query()
                ->with('branch')
                ->where('branch_id', $branchId)
                ->orderByDesc('incurred_at')
                ->limit(8)
                ->get();

        $leaders = array_slice($this->leaderboard->forManager($actor)['leaders'], 0, 3);

        return [
            'role' => 'manager',
            'period' => $period,
            'finance' => [
                'gross_revenue' => Decimal::money($finance['gross_revenue']),
                'expenses' => Decimal::money($finance['expenses_total']),
                'receivables' => $branchId === null ? '0.00' : $this->receivables($branchId),
            ],
            'order_count' => $orders['order_count'],
            'production' => $orders['production'],
            'counts' => $counts,
            'stock_ledgers' => StockLedgerResource::collection($stockLedgers),
            'expenses' => ExpenseResource::collection($expenses),
            'leaderboard' => $leaders,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cashier(User $actor): array
    {
        $branchId = $actor->branch_id;

        $active = $branchId === null
            ? Invoice::query()->whereRaw('1 = 0')->get()
            : Invoice::query()
                ->with(['customer', 'branch'])
                ->where('branch_id', $branchId)
                ->whereIn('status', [0, 1])
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();

        $pickup = $branchId === null
            ? Invoice::query()->whereRaw('1 = 0')->get()
            : Invoice::query()
                ->with(['customer', 'branch'])
                ->where('branch_id', $branchId)
                ->where('status', 2)
                ->where('pickup_status', 0)
                ->orderBy('payment_date')
                ->limit(8)
                ->get();

        $expenses = $branchId === null
            ? Expense::query()->whereRaw('1 = 0')->get()
            : Expense::query()
                ->with('branch')
                ->where('branch_id', $branchId)
                ->orderByDesc('incurred_at')
                ->limit(10)
                ->get();

        $stats = [
            'awaiting_checkout' => $branchId === null ? 0 : Invoice::query()
                ->where('branch_id', $branchId)
                ->where('status', 0)
                ->count(),
            'dp_collected_today' => $branchId === null ? '0.00' : Decimal::money(
                Payment::query()
                    ->where('branch_id', $branchId)
                    ->where('type', 'down_payment')
                    ->whereDate('created_at', now()->toDateString())
                    ->sum('amount')
            ),
            'settlements_today' => $branchId === null ? '0.00' : Decimal::money(
                Payment::query()
                    ->where('branch_id', $branchId)
                    ->where('type', 'settlement')
                    ->whereDate('created_at', now()->toDateString())
                    ->sum('amount')
            ),
            'expenses_today' => $branchId === null ? '0.00' : Decimal::money(
                Expense::query()
                    ->where('branch_id', $branchId)
                    ->whereDate('incurred_at', now()->toDateString())
                    ->sum('amount')
            ),
        ];

        return [
            'role' => 'cashier',
            'stats' => $stats,
            'billing_queue' => InvoiceResource::collection($active),
            'pickup_queue' => InvoiceResource::collection($pickup),
            'expenses' => ExpenseResource::collection($expenses),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function designer(User $actor): array
    {
        $branchId = $actor->branch_id;

        $todayQuery = Invoice::query()
            ->where('branch_id', $branchId)
            ->where('designer_id', $actor->id)
            ->whereDate('created_date', now()->toDateString());

        $recent = Invoice::query()
            ->with(['customer', 'branch'])
            ->where('branch_id', $branchId)
            ->where('designer_id', $actor->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return [
            'role' => 'designer',
            'stats' => [
                'invoices_today' => (clone $todayQuery)->count(),
                'grand_total_today' => Decimal::money((clone $todayQuery)->sum('grand_total')),
            ],
            'recent_invoices' => InvoiceResource::collection($recent),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function production(User $actor): array
    {
        $branchId = $actor->branch_id;

        $orders = Order::query()->whereHas(
            'invoice',
            fn ($query) => $query->where('branch_id', $branchId)
        );

        return [
            'role' => 'production',
            'queued' => (clone $orders)->where('status', 0)->count(),
            'processing' => (clone $orders)->where('status', 1)->count(),
            'completed_today' => (clone $orders)
                ->where('status', 2)
                ->whereDate('updated_at', now()->toDateString())
                ->count(),
            'waste_today_by_unit' => $this->wasteByUnit->forBranch($branchId),
            'queue' => ProductionQueueResource::collection($this->productionQueue->paginate($actor, [])),
        ];
    }

    /**
     * @return array{start: string, end: string}
     */
    private function range(string $period): array
    {
        return match ($period) {
            'weekly' => [
                'start' => now()->startOfWeek()->toDateString(),
                'end' => now()->endOfWeek()->toDateString(),
            ],
            'monthly' => [
                'start' => now()->startOfMonth()->toDateString(),
                'end' => now()->endOfMonth()->toDateString(),
            ],
            default => [
                'start' => now()->toDateString(),
                'end' => now()->toDateString(),
            ],
        };
    }

    private function receivables(?string $branchId): string
    {
        $query = Invoice::query()->whereIn('status', [0, 1]);

        if ($branchId !== null && $branchId !== '' && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        return Decimal::money($query->sum('accounts_receivable'));
    }

    /**
     * @return array{order_count: int, production: array{queued: int, processing: int, completed: int, failed: int}}
     */
    private function orderSummary(?string $branchId, string $startDate, string $endDate): array
    {
        $rows = Order::query()
            ->selectRaw('status, count(*) as total')
            ->whereHas('invoice', function ($query) use ($branchId, $startDate, $endDate): void {
                $query->whereBetween('created_date', [$startDate, $endDate]);

                if ($branchId !== null && $branchId !== '' && $branchId !== 'all') {
                    $query->where('branch_id', $branchId);
                }
            })
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $queued = (int) ($rows[0] ?? 0);
        $processing = (int) ($rows[1] ?? 0);
        $completed = (int) ($rows[2] ?? 0);
        $failed = (int) ($rows[3] ?? 0);

        return [
            'order_count' => $queued + $processing + $completed + $failed,
            'production' => [
                'queued' => $queued,
                'processing' => $processing,
                'completed' => $completed,
                'failed' => $failed,
            ],
        ];
    }
}
