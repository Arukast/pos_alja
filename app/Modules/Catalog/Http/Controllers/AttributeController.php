<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\User;
use App\Modules\Catalog\Actions\CreateAttribute;
use App\Modules\Catalog\Actions\DeleteAttribute;
use App\Modules\Catalog\Actions\UpdateAttribute;
use App\Modules\Catalog\Http\Requests\StoreAttributeRequest;
use App\Modules\Catalog\Http\Requests\UpdateAttributeRequest;
use App\Modules\Catalog\Http\Resources\AttributeResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class AttributeController extends Controller
{
    public function index(Request $request, CatalogQueries $queries): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewAttributes', Attribute::class);

        $branchId = $request->query('branch_id');

        return AttributeResource::collection(
            $queries->attributes($actor, is_string($branchId) ? $branchId : null)
        );
    }

    public function store(StoreAttributeRequest $request, CreateAttribute $action): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $attribute = $action->execute($request->validated(), $actor);

        return response()->json([
            'attribute' => new AttributeResource($attribute),
        ], 201);
    }

    public function update(
        UpdateAttributeRequest $request,
        Attribute $attribute,
        UpdateAttribute $action
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $updated = $action->execute($attribute, $request->validated(), $actor);

        return response()->json([
            'attribute' => new AttributeResource($updated),
        ]);
    }

    public function destroy(Request $request, Attribute $attribute, DeleteAttribute $action): Response
    {
        Gate::authorize('manageAttributes', Attribute::class);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $action->execute($attribute, $actor);

        return response()->noContent();
    }
}
