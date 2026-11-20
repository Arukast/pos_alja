<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttributeType;
use App\Modules\Catalog\Http\Resources\AttributeTypeResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class AttributeTypeController extends Controller
{
    public function index(CatalogQueries $queries): AnonymousResourceCollection
    {
        Gate::authorize('viewAttributeTypes', AttributeType::class);

        return AttributeTypeResource::collection($queries->attributeTypes());
    }
}
