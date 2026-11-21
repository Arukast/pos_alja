<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Models\WastedMaterial;
use App\Modules\Reporting\Queries\BaseCostQuery;
use App\Modules\Reporting\Queries\ExpensesQuery;
use App\Modules\Reporting\Queries\GrossRevenueQuery;
use App\Modules\Reporting\Queries\LeaderboardQuery;
use App\Modules\Reporting\Queries\NetProfitQuery;
use App\Modules\Reporting\Queries\SparklineQuery;
use App\Modules\Sales\Http\Resources\ExpenseResource;
use App\Modules\Sales\Http\Resources\InvoiceResource;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ReportController extends Controller
{
    public function grossRevenue(
        Request $request,
        GrossRevenueQuery $query
    ): JsonResponse {
        Gate::authorize('viewOwnerReports');

        [$branchId, $startDate, $endDate] = $this->range($request);
        $data = $query->get($branchId, $startDate, $endDate);

        return response()->json([
            'grand_total' => Decimal::money($data['grand_total']),
            'cash_total' => Decimal::money($data['cash_total']),
            'transfer_total' => Decimal::money($data['transfer_total']),
            'qris_total' => Decimal::money($data['qris_total']),
            'payments' => $data['payments']->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'invoice_number' => $payment->invoice?->invoice_number,
                'customer_name' => $payment->invoice?->customer?->name,
                'branch_name' => $payment->branch?->name,
                'payment_method' => $payment->payment_method,
                'amount' => Decimal::money($payment->amount),
                'created_at' => $payment->created_at,
            ])->values(),
        ]);
    }

    public function baseCost(Request $request, BaseCostQuery $query): JsonResponse
    {
        Gate::authorize('viewOwnerReports');

        [$branchId, $startDate, $endDate] = $this->range($request);
        $data = $query->get($branchId, $startDate, $endDate);

        return response()->json([
            'total_hpp' => Decimal::money($data['total_hpp']),
            'invoice_cost_total' => Decimal::money($data['invoice_cost_total']),
            'wasted_cost_total' => Decimal::money($data['wasted_cost_total']),
            'invoices' => InvoiceResource::collection($data['invoices']),
            'wasted_materials' => $this->wastePayload($data['wasted_materials']),
        ]);
    }

    public function netProfit(Request $request, NetProfitQuery $query): JsonResponse
    {
        Gate::authorize('viewOwnerReports');

        [$branchId, $startDate, $endDate] = $this->range($request);
        $data = $query->get($branchId, $startDate, $endDate);

        return response()->json([
            'net_profit' => Decimal::money($data['net_profit']),
            'gross_revenue' => Decimal::money($data['gross_revenue']),
            'total_hpp' => Decimal::money($data['total_hpp']),
            'expenses_total' => Decimal::money($data['expenses_total']),
            'margin_rate' => round($data['margin_rate'], 2),
            'invoices' => InvoiceResource::collection($data['invoices']),
            'wasted_materials' => $this->wastePayload($data['wasted_materials']),
            'expenses' => ExpenseResource::collection($data['expenses']),
        ]);
    }

    public function expenses(Request $request, ExpensesQuery $query): JsonResponse
    {
        Gate::authorize('viewOwnerReports');

        [$branchId, $startDate, $endDate] = $this->range($request);
        $data = $query->get($branchId, $startDate, $endDate);

        return response()->json([
            'expenses_total' => Decimal::money($data['expenses_total']),
            'by_category' => collect($data['by_category'])
                ->map(fn (float $total): string => Decimal::money($total))
                ->all(),
            'expenses' => ExpenseResource::collection($data['expenses']),
        ]);
    }

    public function sparkline(Request $request, SparklineQuery $query): JsonResponse
    {
        Gate::authorize('viewOwnerReports');

        $period = $request->string('period')->toString();
        $period = in_array($period, ['daily', 'weekly', 'monthly'], true) ? $period : 'daily';
        $branchId = $request->query('branch_id');

        return response()->json(
            $query->get(is_string($branchId) ? $branchId : 'all', $period)
        );
    }

    public function leaderboard(Request $request, LeaderboardQuery $query): JsonResponse
    {
        Gate::authorize('viewLeaderboard');

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        return response()->json($query->forManager($actor));
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function range(Request $request): array
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        return [
            $data['branch_id'] ?? 'all',
            $data['start_date'],
            $data['end_date'],
        ];
    }

    /**
     * @param  Collection<int, WastedMaterial>  $wastedMaterials
     * @return array<int, array<string, mixed>>
     */
    private function wastePayload(Collection $wastedMaterials): array
    {
        return $wastedMaterials->map(fn (WastedMaterial $waste): array => [
            'id' => $waste->id,
            'material' => $waste->material ? [
                'id' => $waste->material->id,
                'name' => $waste->material->name,
                'cost' => Decimal::money($waste->material->cost),
            ] : null,
            'quantity' => $waste->quantity,
            'wasted_cost' => Decimal::money(
                (float) $waste->quantity * (float) $waste->material->cost
            ),
            'logged_at' => $waste->logged_at,
            'operator' => $waste->operator?->name,
        ])->values()->all();
    }
}
