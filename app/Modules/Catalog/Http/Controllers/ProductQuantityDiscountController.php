<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductQuantityDiscount;
use App\Models\User;
use App\Modules\Catalog\Http\Requests\StoreProductQuantityDiscountRequest;
use App\Modules\Catalog\Http\Requests\UpdateProductQuantityDiscountRequest;
use App\Modules\Catalog\Http\Resources\ProductQuantityDiscountResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class ProductQuantityDiscountController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewProductQuantityDiscounts', ProductQuantityDiscount::class);

        $tiers = ProductQuantityDiscount::query()
            ->with('product')
            ->orderBy('product_id')
            ->orderBy('min_qty')
            ->paginate(20)
            ->withQueryString();

        return ProductQuantityDiscountResource::collection($tiers);
    }

    public function store(StoreProductQuantityDiscountRequest $request): JsonResponse
    {
        $tier = ProductQuantityDiscount::query()->create($request->validated());

        return response()->json([
            'tier' => new ProductQuantityDiscountResource($tier),
        ], 201);
    }

    public function update(UpdateProductQuantityDiscountRequest $request, ProductQuantityDiscount $tier): JsonResponse
    {
        $tier->update($request->validated());

        return response()->json([
            'tier' => new ProductQuantityDiscountResource($tier->fresh(['product'])),
        ]);
    }

    public function destroy(ProductQuantityDiscount $tier): Response
    {
        Gate::authorize('manageProductQuantityDiscounts', ProductQuantityDiscount::class);
        $tier->delete();

        return response()->noContent();
    }
}
