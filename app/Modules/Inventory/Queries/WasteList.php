<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Queries;

use App\Models\Material;
use App\Models\User;
use App\Models\WastedMaterial;
use App\Support\BranchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class WasteList
{
    public function paginate(User $actor, array $filters = []): LengthAwarePaginator
    {
        $query = WastedMaterial::query()
            ->with(['material', 'designer', 'operator', 'branch']);

        BranchScope::apply($query, $actor);

        return $query->orderByDesc('logged_at')->paginate(15)->withQueryString();
    }

    /**
     * @return array{materials: array<int, array<string, mixed>>, designers: array<int, array<string, mixed>>}
     */
    public function options(User $actor): array
    {
        $branchId = $actor->branch_id;

        if ($branchId === null) {
            return ['materials' => [], 'designers' => []];
        }

        $materials = Material::query()
            ->whereHas(
                'branchStocks',
                fn (Builder $query) => $query->where('branch_id', $branchId)
            )
            ->orderBy('name')
            ->get()
            ->map(fn (Material $material): array => [
                'id' => $material->id,
                'name' => $material->name,
                'type' => $material->type,
                'unit' => $material->unit,
                'dimensions' => $material->dimensions,
            ])
            ->values()
            ->all();

        $designers = User::query()
            ->where('role', 'designer')
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values()
            ->all();

        return [
            'materials' => $materials,
            'designers' => $designers,
        ];
    }
}
