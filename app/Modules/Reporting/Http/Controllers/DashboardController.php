<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Reporting\Queries\DashboardQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class DashboardController extends Controller
{
    public function index(Request $request, DashboardQuery $dashboard): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewDashboard');

        return response()->json($dashboard->for($actor, $request));
    }
}
