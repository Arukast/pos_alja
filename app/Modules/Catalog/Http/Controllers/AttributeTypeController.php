<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttributeType;
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

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('manageAttributeTypes', AttributeType::class);

        $type = AttributeType::create($request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:attribute_types,name'],
            'calculation_archetype' => [
                'required',
                'string',
                'in:per_piece,surface_area,linear_dimension,tiered_volume',
            ],
        ]));

        return response()->json(['attribute_type' => new AttributeTypeResource($type)], 201);
    }

    public function destroy(AttributeType $attributeType): Response
    {
        Gate::authorize('manageAttributeTypes', AttributeType::class);
        $attributeType->delete();

        return response()->noContent();
    }
}
