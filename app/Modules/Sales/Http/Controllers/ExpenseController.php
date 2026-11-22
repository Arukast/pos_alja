<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\User;
use App\Modules\Sales\Actions\RecordExpense;
use App\Modules\Sales\Http\Requests\StoreExpenseRequest;
use App\Modules\Sales\Http\Resources\ExpenseResource;
use App\Modules\Sales\Queries\ExpenseList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ExpenseController extends Controller
{
    public function index(Request $request, ExpenseList $expenses): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewAny', Expense::class);

        $branchId = $request->query('branch_id');
        $from = $request->query('from');
        $to = $request->query('to');

        return ExpenseResource::collection($expenses->paginate($actor, [
            'branch_id' => is_string($branchId) ? $branchId : null,
            'from' => is_string($from) ? $from : null,
            'to' => is_string($to) ? $to : null,
        ]));
    }

    public function store(
        StoreExpenseRequest $request,
        RecordExpense $action
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $expense = $action->execute($request->validated(), $actor);

        return response()->json([
            'expense' => new ExpenseResource($expense),
        ], 201);
    }
}
