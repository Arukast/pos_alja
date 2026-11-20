<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Modules\Catalog\Http\Resources\CategoryResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use App\Services\ProductCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class CategoryController extends Controller
{
    public function index(CatalogQueries $queries): AnonymousResourceCollection
    {
        Gate::authorize('viewCategories', ProductCategory::class);

        return CategoryResource::collection($queries->categories());
    }

    public function store(Request $request, ProductCategoryService $service): JsonResponse
    {
        Gate::authorize('manageCategories', ProductCategory::class);

        $category = $service->createCategory($this->validated($request));

        return response()->json(['category' => new CategoryResource($category)], 201);
    }

    public function update(
        Request $request,
        ProductCategory $category,
        ProductCategoryService $service
    ): JsonResponse {
        Gate::authorize('manageCategories', ProductCategory::class);

        $category = $service->updateCategory($category->id, $this->validated($request));

        return response()->json(['category' => new CategoryResource($category)]);
    }

    public function destroy(ProductCategory $category, ProductCategoryService $service): Response
    {
        Gate::authorize('manageCategories', ProductCategory::class);
        $service->deleteCategory($category->id);

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'calculation_archetype' => [
                'required',
                'string',
                'in:per_piece,surface_area,linear_dimension,tiered_volume',
            ],
            'allowed_attribute_types' => ['nullable', 'array'],
            'allowed_attribute_types.*' => ['string', 'max:50'],
        ]);
    }
}
