<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StockLedger;
use App\Models\User;
use App\Modules\Catalog\Http\Resources\StockLedgerResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class StockLedgerController extends Controller
{
    public function index(Request $request, CatalogQueries $queries): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewStockLedgers', StockLedger::class);

        $branchId = $request->query('branch_id');

        return StockLedgerResource::collection($queries->stockLedgers($actor, [
            'branch_id' => is_string($branchId) ? $branchId : null,
        ]));
    }
}
