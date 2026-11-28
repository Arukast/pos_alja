<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Modules\Catalog\Http\Resources\AttributeResource;
use App\Modules\Catalog\Http\Resources\AttributeTypeResource;
use App\Modules\Catalog\Http\Resources\CategoryResource;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class QuoteOptionsController extends Controller
{
    public function index(CatalogQueries $queries): JsonResponse
    {
        Gate::authorize('viewQuoteOptions', Invoice::class);

        $actor = request()->user();

        if (! $actor instanceof User || $actor->branch_id === null) {
            abort(403, 'Desainer harus ditugaskan ke cabang.');
        }

        $branchId = $actor->branch_id;
        $includeInactiveAttributes = request()->boolean('include_inactive_attributes');

        $materials = Material::query()
            ->with('category')
            ->availableInBranch($branchId)
            ->whereHas(
                'category',
                fn (Builder $query) => $query->where('is_active', true)
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

        $attributes = Attribute::query()
            ->with('typeInfo')
            ->when(
                ! $includeInactiveAttributes,
                fn (Builder $query) => $query->where('is_active', true)
            )
            ->where(function (Builder $query) use ($branchId): void {
                $query->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'categories' => CategoryResource::collection(
                ProductCategory::query()->where('is_active', true)->orderBy('name')->get()
            ),
            'products' => ProductResource::collection(
                Product::query()
                    ->where('is_active', true)
                    ->where(function (Builder $query): void {
                        $query->whereNull('product_category_id')
                            ->orWhereHas(
                                'category',
                                fn (Builder $category) => $category->where('is_active', true)
                            );
                    })
                    ->with([
                        'category',
                        'materials',
                        'attributeTypes',
                        'compatibleAttributes.typeInfo',
                        'quantityDiscounts',
                    ])
                    ->orderBy('name')
                    ->get()
            ),
            'materials' => $materials,
            'attributes' => AttributeResource::collection($attributes),
            'attribute_types' => AttributeTypeResource::collection($queries->attributeTypes()),
        ]);
    }
}
