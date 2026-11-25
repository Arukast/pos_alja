<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\User;
use App\Modules\Catalog\Actions\CreateCategory;
use App\Modules\Catalog\Actions\DeleteCategory;
use App\Modules\Catalog\Actions\UpdateCategory;
use App\Modules\Catalog\Http\Requests\StoreCategoryRequest;
use App\Modules\Catalog\Http\Requests\UpdateCategoryRequest;
use App\Modules\Catalog\Http\Resources\CategoryResource;
use App\Modules\Catalog\Queries\CatalogQueries;
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

    public function store(StoreCategoryRequest $request, CreateCategory $action): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $category = $action->execute($request->validated(), $actor);

        return response()->json([
            'category' => new CategoryResource($category),
        ], 201);
    }

    public function update(
        UpdateCategoryRequest $request,
        ProductCategory $category,
        UpdateCategory $action
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $updated = $action->execute($category, $request->validated(), $actor);

        return response()->json([
            'category' => new CategoryResource($updated),
        ]);
    }

    public function destroy(
        Request $request,
        ProductCategory $category,
        DeleteCategory $action
    ): Response {
        Gate::authorize('manageCategories', ProductCategory::class);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $action->execute($category, $actor);

        return response()->noContent();
    }
}
