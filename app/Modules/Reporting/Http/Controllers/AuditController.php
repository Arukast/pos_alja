<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Modules\Audit\Queries\AuditLogList;
use App\Modules\Reporting\Http\Resources\ActivityLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class AuditController extends Controller
{
    public function index(Request $request, AuditLogList $logs): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', ActivityLog::class);

        $search = trim($request->string('search')->toString());

        return ActivityLogResource::collection($logs->paginate([
            'search' => $search === '' ? null : $search,
            'sort' => $request->query('sort'),
            'direction' => $request->query('direction'),
        ]));
    }

    public function show(ActivityLog $activityLog): JsonResponse
    {
        Gate::authorize('view', $activityLog);

        $activityLog->loadMissing(['branch', 'user']);

        return response()->json([
            'activity_log' => new ActivityLogResource($activityLog),
        ]);
    }
}
