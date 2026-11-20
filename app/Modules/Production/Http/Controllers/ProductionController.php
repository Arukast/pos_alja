<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\WastedMaterial;
use App\Modules\Production\Http\Resources\ProductionQueueResource;
use App\Modules\Production\Queries\ProductionQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ProductionController extends Controller
{
    public function queue(
        Request $request,
        ProductionQueue $queue
    ): AnonymousResourceCollection {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewQueue', Order::class);

        $branchId = $request->query('branch_id');

        return ProductionQueueResource::collection($queue->paginate($actor, [
            'branch_id' => is_string($branchId) ? $branchId : null,
        ]));
    }

    public function summary(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewSummary', Order::class);

        $branchId = $actor->branch_id;

        $orders = Order::query()->whereHas(
            'invoice',
            fn (Builder $query) => $query->where('branch_id', $branchId)
        );

        $queued = (clone $orders)->where('status', 0)->count();
        $processing = (clone $orders)->where('status', 1)->count();
        $completedToday = (clone $orders)
            ->where('status', 2)
            ->whereDate('updated_at', now()->toDateString())
            ->count();

        $wasteToday = WastedMaterial::query()
            ->where('branch_id', $branchId)
            ->whereDate('logged_at', now()->toDateString())
            ->sum('quantity');

        return response()->json([
            'queued' => $queued,
            'processing' => $processing,
            'completed_today' => $completedToday,
            'waste_today' => (float) $wasteToday,
        ]);
    }
}
