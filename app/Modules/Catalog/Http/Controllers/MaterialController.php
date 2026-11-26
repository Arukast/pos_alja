<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Material;
use App\Models\User;
use App\Modules\Catalog\Actions\SetMinimumStock;
use App\Modules\Catalog\Http\Requests\AdjustStockRequest;
use App\Modules\Catalog\Http\Requests\SetMinimumStockRequest;
use App\Modules\Catalog\Http\Requests\StoreMaterialRequest;
use App\Modules\Catalog\Http\Resources\MaterialResource;
use App\Modules\Catalog\Http\Resources\ProductionMaterialResource;
use App\Modules\Catalog\Queries\MaterialQueries;
use App\Modules\Inventory\Actions\AdjustStock;
use App\Services\InventoryService;
use App\Support\BranchScope;
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
        InventoryService $inventory
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $data = $request->validated();
        $initialStock = (float) ($data['initial_stock'] ?? 0);
        $minStock = (float) ($data['min_stock'] ?? 0);

        $material = DB::transaction(function () use ($actor, $data, $inventory, $initialStock, $minStock): Material {
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

            return $material;
        });

        return response()->json([
            'material' => new MaterialResource(
                $material->load(['category', 'branchStocks.branch'])
            ),
        ], 201);
    }

    public function update(Request $request, Material $material): JsonResponse
    {
        Gate::authorize('update', $material);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'product_category_id' => ['required', 'uuid', 'exists:product_categories,id'],
            'type' => ['nullable', 'string', 'max:50'],
            'dimensions' => ['nullable', 'string', 'max:100'],
            'unit' => ['sometimes', 'string', Rule::in(MaterialUnit::VALUES)],
            'cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
        ]);

        $material->update($data);

        return response()->json([
            'material' => new MaterialResource(
                $material->fresh(['category', 'branchStocks.branch'])
            ),
        ]);
    }

    public function destroy(Material $material, InventoryService $inventory): Response
    {
        Gate::authorize('delete', $material);
        $inventory->deleteGlobalMaterial($material->id);

        return response()->noContent();
    }

    public function restore(string $material): JsonResponse
    {
        $model = Material::query()->withTrashed()->findOrFail($material);
        Gate::authorize('restore', $model);
        $model->restore();

        return response()->json([
            'material' => new MaterialResource(
                $model->fresh(['category', 'branchStocks.branch'])
            ),
        ]);
    }

    public function stock(
        Request $request,
        Material $material,
        InventoryService $inventory
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

        $inventory->addStock(
            $material,
            (float) $data['quantity'],
            $data['notes'] ?? null,
            $branchId
        );

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
        InventoryService $inventory
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

        $exists = $material->branchStocks()->where('branch_id', $branchId)->exists();

        if (! $exists) {
            $inventory->initializeBranchStock(
                $material->id,
                $branchId,
                (float) ($data['initial_stock'] ?? 0),
                (float) ($data['min_stock'] ?? 0)
            );
        }

        return response()->json([
            'material' => new MaterialResource(
                $material->fresh(['category', 'branchStocks.branch'])
            ),
        ]);
    }

    public function minStock(
        SetMinimumStockRequest $request,
        Material $material,
        SetMinimumStock $action
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
}
