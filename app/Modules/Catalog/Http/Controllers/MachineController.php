<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\User;
use App\Modules\Catalog\Http\Resources\MachineResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class MachineController extends Controller
{
    public function index(Request $request, CatalogQueries $queries): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewAny', Machine::class);

        $branchId = $request->query('branch_id');
        $search = trim($request->string('search')->toString());

        return MachineResource::collection($queries->machines($actor, [
            'branch_id' => is_string($branchId) ? $branchId : null,
            'search' => $search === '' ? null : $search,
            'include_deleted' => $request->boolean('include_deleted'),
        ]));
    }
}
