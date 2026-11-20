<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Modules\Identity\Http\Resources\BranchResource;
use App\Modules\Identity\Queries\BranchList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class BranchController extends Controller
{
    public function index(Request $request, BranchList $branches): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Branch::class);

        $search = trim($request->string('search')->toString());

        return BranchResource::collection(
            $branches->paginate(
                $search === '' ? null : $search,
                $request->boolean('include_deleted')
            )
        );
    }

    public function show(Branch $branch): JsonResponse
    {
        Gate::authorize('view', $branch);

        return response()->json([
            'branch' => new BranchResource($branch),
        ]);
    }
}
