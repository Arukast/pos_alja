<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Invoice;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Modules\Catalog\Http\Resources\AttributeResource;
use App\Modules\Catalog\Http\Resources\CategoryResource;
use App\Modules\Catalog\Http\Resources\ProductResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class QuoteOptionsController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewQuoteOptions', Invoice::class);

        $actor = request()->user();

        if (! $actor instanceof User || $actor->branch_id === null) {
            abort(403, 'Desainer harus ditugaskan ke cabang.');
        }

        $branchId = $actor->branch_id;

        $materials = Material::query()
            ->with('category')
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
                'dimensions' => $material->dimensions,
                'category' => $material->category ? [
                    'id' => $material->category->id,
                    'name' => $material->category->name,
                ] : null,
            ])
            ->values();

        $machines = Machine::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Machine $machine): array => [
                'id' => $machine->id,
                'name' => $machine->name,
            ])
            ->values();

        $attributes = Attribute::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($branchId): void {
                $query->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'categories' => CategoryResource::collection(
                ProductCategory::query()->orderBy('name')->get()
            ),
            'products' => ProductResource::collection(
                Product::query()->where('is_active', true)->with('category')->orderBy('name')->get()
            ),
            'materials' => $materials,
            'machines' => $machines,
            'attributes' => AttributeResource::collection($attributes),
        ]);
    }
}
