<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttributeType;
use App\Models\User;
use App\Modules\Catalog\Actions\CreateAttributeType;
use App\Modules\Catalog\Actions\DeleteAttributeType;
use App\Modules\Catalog\Http\Requests\StoreAttributeTypeRequest;
use App\Modules\Catalog\Http\Resources\AttributeTypeResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class AttributeTypeController extends Controller
{
    public function index(CatalogQueries $queries): AnonymousResourceCollection
    {
        Gate::authorize('viewAttributeTypes', AttributeType::class);

        return AttributeTypeResource::collection($queries->attributeTypes());
    }

    public function store(StoreAttributeTypeRequest $request, CreateAttributeType $action): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $type = $action->execute($request->validated(), $actor);

        return response()->json(['attribute_type' => new AttributeTypeResource($type)], 201);
    }

    public function destroy(
        Request $request,
        AttributeType $attributeType,
        DeleteAttributeType $action
    ): Response {
        Gate::authorize('manageAttributeTypes', AttributeType::class);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $action->execute($attributeType, $actor);

        return response()->noContent();
    }
}
