<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Modules\Catalog\Actions\CreateProduct;
use App\Modules\Catalog\Actions\DeleteProduct;
use App\Modules\Catalog\Actions\RestoreProduct;
use App\Modules\Catalog\Actions\UpdateProduct;
use App\Modules\Catalog\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Http\Requests\UpdateProductRequest;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class ProductController extends Controller
{
    public function index(Request $request, CatalogQueries $queries): AnonymousResourceCollection
    {
        Gate::authorize('viewProducts', Product::class);

        $actor = $request->user();
        $search = trim($request->string('search')->toString());
        $categoryId = $request->query('category_id');
        $rawIsActive = $request->query('is_active');
        $isActive = match (is_string($rawIsActive) ? strtolower(trim($rawIsActive)) : null) {
            '1', 'true' => true,
            '0', 'false' => false,
            default => null,
        };
        $includeDeleted = $actor instanceof User
            && $actor->role === 'owner'
            && $request->boolean('include_deleted');

        return ProductResource::collection($queries->products([
            'search' => $search === '' ? null : $search,
            'category_id' => is_string($categoryId) && trim($categoryId) !== '' ? trim($categoryId) : null,
            'is_active' => $isActive,
            'include_deleted' => $includeDeleted,
        ]));
    }

    public function store(StoreProductRequest $request, CreateProduct $action): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $product = $action->execute($request->validated(), $actor);

        return response()->json([
            'product' => new ProductResource($product),
        ], 201);
    }

    public function update(
        UpdateProductRequest $request,
        Product $product,
        UpdateProduct $action
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $updated = $action->execute($product, $request->validated(), $actor);

        return response()->json([
            'product' => new ProductResource($updated),
        ]);
    }

    public function destroy(Request $request, Product $product, DeleteProduct $action): Response
    {
        Gate::authorize('manageProducts', Product::class);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $action->execute($product, $actor);

        return response()->noContent();
    }

    public function restore(Request $request, string $product, RestoreProduct $action): JsonResponse
    {
        Gate::authorize('manageProducts', Product::class);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $model = Product::query()->withTrashed()->findOrFail($product);
        $restored = $action->execute($model, $actor);

        return response()->json([
            'product' => new ProductResource($restored),
        ]);
    }
}
