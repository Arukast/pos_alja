<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttributeType;
use App\Models\User;
use App\Modules\Catalog\Actions\CreateAttributeType;
use App\Modules\Catalog\Actions\UpdateAttributeType;
use App\Modules\Catalog\Http\Requests\StoreAttributeTypeRequest;
use App\Modules\Catalog\Http\Requests\UpdateAttributeTypeRequest;
use App\Modules\Catalog\Http\Resources\AttributeTypeResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class AttributeTypeController extends Controller
{
    public function index(CatalogQueries $queries): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AttributeType::class);

        return AttributeTypeResource::collection($queries->attributeTypes());
    }

    public function store(StoreAttributeTypeRequest $request, CreateAttributeType $action): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $type = $action->execute($request->validated(), $actor);

        return response()->json([
            'attribute_type' => new AttributeTypeResource($type),
        ], 201);
    }

    public function update(
        UpdateAttributeTypeRequest $request,
        AttributeType $type,
        UpdateAttributeType $action
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $updated = $action->execute($type, $request->validated(), $actor);

        return response()->json([
            'attribute_type' => new AttributeTypeResource($updated),
        ]);
    }
}
