<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Material;
use App\Models\User;
use App\Modules\Catalog\Http\Requests\AdjustStockRequest;
use App\Modules\Catalog\Http\Requests\StoreMaterialRequest;
use App\Modules\Catalog\Http\Resources\MaterialResource;
use App\Modules\Catalog\Http\Resources\ProductionMaterialResource;
use App\Modules\Catalog\Queries\MaterialQueries;
use App\Modules\Inventory\Actions\AdjustStock;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use App\Support\BranchScope;
use App\Support\Decimal;
use App\Support\MaterialUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class MaterialController extends Controller
{
    public function index(Request $request, MaterialQueries $queries): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewAny', Material::class);

        $branchId = $request->query('branch_id');
        $search = trim($request->string('search')->toString());

        $materials = $queries->paginate($actor, [
            'branch_id' => is_string($branchId) ? $branchId : null,
            'search' => $search === '' ? null : $search,
            'include_deleted' => $request->boolean('include_deleted'),
            'low_stock' => $request->boolean('low_stock'),
        ]);

        if ($actor->role === 'production') {
            return ProductionMaterialResource::collection($materials);
        }

        return MaterialResource::collection($materials);
    }

    public function store(
        StoreMaterialRequest $request,
        InventoryService $inventory,
        AuditLogger $audit
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $data = $request->validated();
        $initialStock = (float) ($data['initial_stock'] ?? 0);
        $minStock = (float) ($data['min_stock'] ?? 0);

        $material = DB::transaction(function () use ($actor, $data, $inventory, $audit, $initialStock, $minStock): Material {
            $material = $inventory->createGlobalMaterial($data);

            $branches = $actor->role === 'owner'
                ? Branch::query()->get()
                : Branch::query()->whereKey($actor->branch_id)->get();

            foreach ($branches as $branch) {
                $inventory->initializeBranchStock(
                    $material->id,
                    $branch->id,
                    $initialStock,
                    $minStock
                );
            }

            $audit->record(
                $actor,
                'CREATE',
                "Membuat bahan: {$material->name}",
                $material,
                ['after' => $this->snapshot($material)],
            );

            return $material;
        });

        return response()->json([
            'material' => new MaterialResource(
                $material->load(['category', 'branchStocks.branch'])
            ),
        ], 201);
    }

    public function update(Request $request, Material $material, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('update', $material);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'product_category_id' => ['required', 'uuid', 'exists:product_categories,id'],
            'type' => ['nullable', 'string', 'max:50'],
            'dimensions' => ['nullable', 'string', 'max:100'],
            'unit' => ['sometimes', 'string', Rule::in(MaterialUnit::VALUES)],
            'cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
        ]);

        DB::transaction(function () use ($material, $data, $actor, $audit): void {
            $before = $this->snapshot($material);

            $material->update($data);

            $audit->record(
                $actor,
                'UPDATE',
                "Memperbarui bahan: {$material->name}",
                $material,
                [
                    'before' => $before,
                    'after' => $this->snapshot($material),
                ],
            );
        });

        return response()->json([
            'material' => new MaterialResource(
                $material->fresh(['category', 'branchStocks.branch'])
            ),
        ]);
    }

    public function destroy(Request $request, Material $material, InventoryService $inventory, AuditLogger $audit): Response
    {
        Gate::authorize('delete', $material);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        DB::transaction(function () use ($material, $inventory, $actor, $audit): void {
            $before = $this->snapshot($material);

            $inventory->deleteGlobalMaterial($material->id);

            $audit->record(
                $actor,
                'DELETE',
                "Menghapus bahan: {$material->name}",
                $material,
                [
                    'before' => $before,
                    'after' => null,
                ],
            );
        });

        return response()->noContent();
    }

    public function restore(Request $request, string $material, AuditLogger $audit): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $model = Material::query()->withTrashed()->findOrFail($material);
        Gate::authorize('restore', $model);

        DB::transaction(function () use ($model, $actor, $audit): void {
            $wasTrashed = $model->trashed();

            $model->restore();

            if ($wasTrashed) {
                $audit->record(
                    $actor,
                    'RESTORE',
                    "Memulihkan bahan: {$model->name}",
                    $model,
                    ['after' => $this->snapshot($model)],
                );
            }
        });

        return response()->json([
            'material' => new MaterialResource(
                $model->fresh(['category', 'branchStocks.branch'])
            ),
        ]);
    }

    public function stock(
        Request $request,
        Material $material,
        InventoryService $inventory,
        AuditLogger $audit
    ): JsonResponse {
        Gate::authorize('restock', $material);

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
        ]);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $branchId = BranchScope::writeBranchId($actor, $data['branch_id'] ?? null);

        DB::transaction(function () use ($inventory, $material, $data, $branchId, $actor, $audit): void {
            $movement = $inventory->addStock(
                $material,
                (float) $data['quantity'],
                $data['notes'] ?? null,
                $branchId
            );

            $audit->record(
                $actor,
                'STOCK',
                "Menambah stok bahan: {$material->name}",
                $material,
                [
                    'quantity' => (float) $data['quantity'],
                    'stock_before' => $movement['old_stock'],
                    'stock_after' => $movement['new_stock'],
                    'delta' => round($movement['new_stock'] - $movement['old_stock'], 2),
                    'notes' => $data['notes'] ?? null,
                    'branch_id' => $branchId,
                    'ledger_id' => $movement['ledger_id'],
                ],
                $branchId,
            );
        });

        return response()->json([
            'material' => new MaterialResource(
                $material->fresh(['category', 'branchStocks.branch'])
            ),
        ]);
    }

    public function adjust(
        AdjustStockRequest $request,
        Material $material,
        AdjustStock $action
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $action->execute($material, $request->validated(), $actor);

        return response()->json([
            'material' => new MaterialResource(
                $material->fresh(['category', 'branchStocks.branch'])
            ),
        ]);
    }

    public function activate(
        Request $request,
        Material $material,
        InventoryService $inventory,
        AuditLogger $audit
    ): JsonResponse {
        Gate::authorize('activate', $material);

        $data = $request->validate([
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'initial_stock' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'min_stock' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $branchId = BranchScope::writeBranchId($actor, $data['branch_id'] ?? null);
        $initialStock = (float) ($data['initial_stock'] ?? 0);
        $minStock = (float) ($data['min_stock'] ?? 0);

        DB::transaction(function () use ($material, $branchId, $initialStock, $minStock, $inventory, $actor, $audit): void {
            $stock = $material->branchStocks()->where('branch_id', $branchId)->first();

            if ($stock === null) {
                $result = $inventory->initializeBranchStock(
                    $material->id,
                    $branchId,
                    $initialStock,
                    $minStock
                );

                $stock = $result['stock'];
                $createdStockRow = $result['created'];
                $stockBefore = $createdStockRow ? 0.0 : (float) $stock->current_stock;
            } else {
                $createdStockRow = false;
                $stockBefore = (float) $stock->current_stock;
            }

            $audit->record(
                $actor,
                'ACTIVATE',
                "Mengaktifkan bahan di cabang: {$material->name}",
                $material,
                [
                    'branch_id' => $branchId,
                    'created_stock_row' => $createdStockRow,
                    'initial_stock' => $createdStockRow ? $initialStock : 0.0,
                    'min_stock' => (float) $stock->min_stock,
                    'stock_before' => $stockBefore,
                    'stock_after' => (float) $stock->current_stock,
                ],
                $branchId,
            );
        });

        return response()->json([
            'material' => new MaterialResource(
                $material->fresh(['category', 'branchStocks.branch'])
            ),
        ]);
    }

    public function similar(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('create', Material::class);

        $name = trim($request->string('name')->toString());

        $materials = Material::query()
            ->with(['category', 'branchStocks.branch'])
            ->when($name !== '', fn ($query) => $query->where('name', 'like', "%{$name}%"))
            ->orderBy('name')
            ->limit(5)
            ->get();

        return MaterialResource::collection($materials);
    }

    /**
     * @return array{
     *     name: string,
     *     product_category_id: string,
     *     type: string|null,
     *     dimensions: string|null,
     *     unit: string,
     *     cost: string,
     *     price: string
     * }
     */
    private function snapshot(Material $material): array
    {
        return [
            'name' => $material->name,
            'product_category_id' => $material->product_category_id,
            'type' => $material->type,
            'dimensions' => $material->dimensions,
            'unit' => $material->unit,
            'cost' => Decimal::money($material->cost),
            'price' => Decimal::money($material->price),
        ];
    }
}
