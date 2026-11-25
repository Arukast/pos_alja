<?php

declare(strict_types=1);

namespace App\Modules\Production\Queries;

use Illuminate\Support\Facades\DB;
use stdClass;

final class WasteByUnit
{
    /**
     * @return array<int, array{unit: string, quantity: float}>
     */
    public function forBranch(?string $branchId): array
    {
        if ($branchId === null) {
            return [];
        }

        $unit = "COALESCE(materials.unit, 'pcs')";

        return DB::table('wasted_materials')
            ->leftJoin('materials', 'materials.id', '=', 'wasted_materials.material_id')
            ->where('wasted_materials.branch_id', $branchId)
            ->whereBetween('wasted_materials.logged_at', [now()->startOfDay(), now()->endOfDay()])
            ->groupBy(DB::raw($unit))
            ->orderBy(DB::raw($unit))
            ->selectRaw("{$unit} as unit, ROUND(SUM(wasted_materials.quantity), 2) as quantity")
            ->get()
            ->map(fn (stdClass $row): array => [
                'unit' => (string) $row->unit,
                'quantity' => (float) $row->quantity,
            ])
            ->all();
    }
}
