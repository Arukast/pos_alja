<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Queries;

use App\Models\Invoice;
use App\Models\WastedMaterial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class BaseCostQuery
{
    /**
     * @return array{total_hpp: float, invoice_cost_total: float, wasted_cost_total: float, invoices: Collection<int, Invoice>, wasted_materials: Collection<int, WastedMaterial>}
     */
    public function get(?string $branchId, string $startDate, string $endDate): array
    {
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
        $wastedCost = $wastedMaterials->sum(
            fn (WastedMaterial $waste): float => (float) $waste->quantity * (float) ($waste->material ? $waste->material->cost : 0)
        );

        return [
            'total_hpp' => $invoiceCost + $wastedCost,
            'invoice_cost_total' => $invoiceCost,
            'wasted_cost_total' => $wastedCost,
            'invoices' => $invoices,
            'wasted_materials' => $wastedMaterials,
        ];
    }

    private function scopeBranch(Builder $query, ?string $branchId): void
    {
        if ($branchId !== null && $branchId !== '' && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }
    }
}
