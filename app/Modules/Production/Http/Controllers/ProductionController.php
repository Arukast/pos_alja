<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Modules\Production\Actions\CompleteProduction;
use App\Modules\Production\Actions\FailProduction;
use App\Modules\Production\Actions\ReopenProduction;
use App\Modules\Production\Actions\StartProduction;
use App\Modules\Production\Http\Requests\FailProductionRequest;
use App\Modules\Production\Http\Resources\ProductionQueueResource;
use App\Modules\Production\Queries\ProductionQueue;
use App\Modules\Production\Queries\WasteByUnit;
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

    public function start(
        Request $request,
        Order $order,
        StartProduction $action
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('start', $order);

        return response()->json([
            'order' => new ProductionQueueResource($action->execute($order, $actor)),
        ]);
    }

    public function complete(
        Request $request,
        Order $order,
        CompleteProduction $action
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('complete', $order);

        return response()->json([
            'order' => new ProductionQueueResource($action->execute($order, $actor)),
        ]);
    }

    public function fail(
        FailProductionRequest $request,
        Order $order,
        FailProduction $action
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        return response()->json([
            'order' => new ProductionQueueResource(
                $action->execute($order, $actor, (string) $request->validated('note'))
            ),
        ]);
    }

    public function reopen(
        Request $request,
        Order $order,
        ReopenProduction $action
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('reopen', $order);

        return response()->json([
            'order' => new ProductionQueueResource($action->execute($order, $actor)),
        ]);
    }

    public function summary(Request $request, WasteByUnit $wasteByUnit): JsonResponse
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
        $failed = (clone $orders)->where('status', 3)->count();

        return response()->json([
            'queued' => $queued,
            'processing' => $processing,
            'completed_today' => $completedToday,
            'failed' => $failed,
            'waste_today_by_unit' => $wasteByUnit->forBranch($branchId),
        ]);
    }
}
