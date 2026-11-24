<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\User;
use App\Modules\Inventory\Actions\RecordPurchase;
use App\Modules\Inventory\Http\Requests\StorePurchaseRequest;
use App\Modules\Inventory\Http\Resources\PurchaseResource;
use App\Modules\Inventory\Queries\PurchaseList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class PurchaseController extends Controller
{
    public function index(Request $request, PurchaseList $purchases): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewAny', Purchase::class);

        $branchId = $request->query('branch_id');

        return PurchaseResource::collection(
            $purchases->paginate($actor, is_string($branchId) ? $branchId : null)
        );
    }

    public function store(StorePurchaseRequest $request, RecordPurchase $action): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $result = $action->execute($request->validated(), $actor);

        return response()->json([
            'purchase' => new PurchaseResource($result['purchase']),
        ], $result['created'] ? 201 : 200);
    }
}
