<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\User;
use App\Modules\Catalog\Http\Resources\MaterialResource;
use App\Modules\Catalog\Http\Resources\ProductionMaterialResource;
use App\Modules\Catalog\Queries\MaterialQueries;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class MaterialController extends Controller
{
    public function index(Request $request, MaterialQueries $queries): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewAny', Material::class);

        $branchId = $request->query('branch_id');
        $search = trim($request->string('search')->toString());

        $materials = $queries->paginate($actor, [
            'branch_id' => is_string($branchId) ? $branchId : null,
            'search' => $search === '' ? null : $search,
            'include_deleted' => $request->boolean('include_deleted'),
        ]);

        if ($actor->role === 'production') {
            return ProductionMaterialResource::collection($materials);
        }

        return MaterialResource::collection($materials);
    }
}
