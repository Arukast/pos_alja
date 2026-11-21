<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\User;
use App\Modules\Catalog\Http\Resources\AttributeResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class AttributeController extends Controller
{
    public function index(Request $request, CatalogQueries $queries): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewAttributes', Attribute::class);

        $branchId = $request->query('branch_id');

        return AttributeResource::collection(
            $queries->attributes($actor, is_string($branchId) ? $branchId : null)
        );
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('manageAttributes', Attribute::class);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:finishing,cut,display'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
        ]);

        $data['branch_id'] = $actor->role === 'owner'
            ? ($data['branch_id'] ?? null)
            : $actor->branch_id;

        $attribute = Attribute::create($data)->load('branch');

        return response()->json([
            'attribute' => new AttributeResource($attribute),
        ], 201);
    }

    public function update(Request $request, Attribute $attribute): JsonResponse
    {
        Gate::authorize('manageAttributes', Attribute::class);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        if ($actor->role === 'manager' && $attribute->branch_id !== $actor->branch_id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:finishing,cut,display'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
        ]);

        if ($actor->role === 'manager') {
            $data['branch_id'] = $actor->branch_id;
        }

        $attribute->update($data);

        return response()->json([
            'attribute' => new AttributeResource($attribute->fresh('branch')),
        ]);
    }

    public function destroy(Attribute $attribute): Response
    {
        Gate::authorize('manageAttributes', Attribute::class);

        $actor = request()->user();

        if ($actor instanceof User && $actor->role === 'manager' && $attribute->branch_id !== $actor->branch_id) {
            abort(403);
        }

        $attribute->delete();

        return response()->noContent();
    }
}
