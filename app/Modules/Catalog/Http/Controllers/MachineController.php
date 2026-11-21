<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\User;
use App\Modules\Catalog\Http\Resources\MachineResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use App\Services\MachineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class MachineController extends Controller
{
    public function index(Request $request, CatalogQueries $queries): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewAny', Machine::class);

        $branchId = $request->query('branch_id');
        $search = trim($request->string('search')->toString());

        return MachineResource::collection($queries->machines($actor, [
            'branch_id' => is_string($branchId) ? $branchId : null,
            'search' => $search === '' ? null : $search,
            'include_deleted' => $request->boolean('include_deleted'),
        ]));
    }

    public function store(Request $request, MachineService $service): JsonResponse
    {
        Gate::authorize('create', Machine::class);

        $data = $this->validated($request);
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        if ($actor->role === 'manager') {
            $data['branch_id'] = $actor->branch_id;
        }

        $machine = $service->createMachine($data, $actor);

        return response()->json([
            'machine' => new MachineResource($machine->load('branch')),
        ], 201);
    }

    public function update(
        Request $request,
        Machine $machine,
        MachineService $service
    ): JsonResponse {
        Gate::authorize('update', $machine);

        $data = $this->validated($request);
        $actor = $request->user();

        if ($actor instanceof User && $actor->role === 'manager') {
            $data['branch_id'] = $actor->branch_id;
        }

        $machine = $service->updateMachine($machine, $data, $actor);

        return response()->json([
            'machine' => new MachineResource($machine->load('branch')),
        ]);
    }

    public function destroy(Machine $machine, MachineService $service): Response
    {
        Gate::authorize('delete', $machine);

        $actor = request()->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $service->deleteMachine($machine, $actor);

        return response()->noContent();
    }

    public function restore(string $machine, MachineService $service): JsonResponse
    {
        $model = Machine::query()->withTrashed()->findOrFail($machine);
        Gate::authorize('restore', $model);

        $actor = request()->user();
        $service->restoreMachine($model, $actor);

        return response()->json([
            'machine' => new MachineResource($model->fresh('branch')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
        ]);
    }
}
