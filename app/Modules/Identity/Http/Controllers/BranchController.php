<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Modules\Identity\Http\Requests\StoreBranchRequest;
use App\Modules\Identity\Http\Requests\UpdateBranchRequest;
use App\Modules\Identity\Http\Resources\BranchResource;
use App\Modules\Identity\Queries\BranchList;
use App\Services\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
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

    public function store(StoreBranchRequest $request, BranchService $service): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $branch = $service->createBranch($request->validated(), $actor);

        return response()->json([
            'branch' => new BranchResource($branch),
        ], 201);
    }

    public function update(
        UpdateBranchRequest $request,
        Branch $branch,
        BranchService $service
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $updated = $service->updateBranch($branch, $request->validated(), $actor);

        return response()->json([
            'branch' => new BranchResource($updated),
        ]);
    }

    public function status(Branch $branch, BranchService $service): JsonResponse
    {
        Gate::authorize('update', $branch);

        $actor = request()->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $updated = $service->toggleBranchStatus($branch, $actor);

        return response()->json([
            'branch' => new BranchResource($updated),
        ]);
    }

    public function destroy(Branch $branch, BranchService $service): Response
    {
        Gate::authorize('delete', $branch);

        $actor = request()->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $service->deleteBranch($branch, $actor);

        return response()->noContent();
    }
}
