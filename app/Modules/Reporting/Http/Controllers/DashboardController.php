<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Reporting\Http\Requests\DashboardRequest;
use App\Modules\Reporting\Queries\DashboardQuery;
use Illuminate\Http\JsonResponse;

final class DashboardController extends Controller
{
    public function index(DashboardRequest $request, DashboardQuery $dashboard): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        return response()->json(
            $dashboard->for($actor, $request->period(), $request->resolvedBranchId())
        );
    }
}
