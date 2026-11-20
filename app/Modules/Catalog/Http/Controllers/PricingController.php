<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pricing;
use App\Models\User;
use App\Modules\Catalog\Http\Resources\PricingResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class PricingController extends Controller
{
    public function index(Request $request, CatalogQueries $queries): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewPricing', Pricing::class);

        $branchId = $request->query('branch_id');

        return PricingResource::collection($queries->pricing($actor, [
            'branch_id' => is_string($branchId) ? $branchId : null,
        ]));
    }
}
