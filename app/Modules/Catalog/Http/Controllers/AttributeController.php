<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\User;
use App\Modules\Catalog\Http\Resources\AttributeResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
}
