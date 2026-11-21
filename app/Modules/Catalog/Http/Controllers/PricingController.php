<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pricing;
use App\Models\User;
use App\Modules\Catalog\Http\Resources\PricingResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class PricingController extends Controller
{
    public function index(Request $request, CatalogQueries $queries): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewPricing', Pricing::class);

        $branchId = $request->query('branch_id');

        return PricingResource::collection($queries->pricing($actor, [
            'branch_id' => is_string($branchId) ? $branchId : null,
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('managePricing', Pricing::class);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $data = $request->validate([
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'material_id' => ['required', 'uuid', 'exists:materials,id'],
            'customer_type' => ['required', 'string', 'max:50'],
            'min_qty' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'profit' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ]);

        $data['branch_id'] = $actor->role === 'owner'
            ? ($data['branch_id'] ?? null)
            : $actor->branch_id;

        if ($data['branch_id'] === null) {
            abort(422, 'Cabang wajib diisi.');
        }

        $data['profit'] = $data['profit'] ?? max(0, (float) $data['price'] - (float) $data['cost']);

        $pricing = Pricing::create($data)->load(['product', 'material', 'branch']);

        return response()->json([
            'pricing' => new PricingResource($pricing),
        ], 201);
    }

    public function update(Request $request, Pricing $pricing): JsonResponse
    {
        Gate::authorize('managePricing', Pricing::class);
        $this->assertBranch($request->user(), $pricing);

        $data = $request->validate([
            'min_qty' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'profit' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ]);

        $data['profit'] = $data['profit'] ?? max(0, (float) $data['price'] - (float) $data['cost']);

        $pricing->update($data);

        return response()->json([
            'pricing' => new PricingResource($pricing->fresh(['product', 'material', 'branch'])),
        ]);
    }

    public function destroy(Pricing $pricing): Response
    {
        Gate::authorize('managePricing', Pricing::class);
        $this->assertBranch(request()->user(), $pricing);
        $pricing->delete();

        return response()->noContent();
    }

    public function cloneTiers(Request $request): JsonResponse
    {
        Gate::authorize('managePricing', Pricing::class);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $data = $request->validate([
            'from_material_id' => ['required', 'uuid', 'exists:materials,id'],
            'to_material_id' => ['required', 'uuid', 'exists:materials,id'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
        ]);

        $branchId = $actor->role === 'owner'
            ? ($data['branch_id'] ?? null)
            : $actor->branch_id;

        if ($branchId === null) {
            abort(422, 'Cabang wajib diisi.');
        }

        $rows = Pricing::query()
            ->where('branch_id', $branchId)
            ->where('material_id', $data['from_material_id'])
            ->get();

        foreach ($rows as $row) {
            Pricing::query()->updateOrCreate([
                'branch_id' => $branchId,
                'product_id' => $row->product_id,
                'material_id' => $data['to_material_id'],
                'customer_type' => $row->customer_type,
                'min_qty' => $row->min_qty,
            ], [
                'price' => $row->price,
                'cost' => $row->cost,
                'profit' => $row->profit,
            ]);
        }

        return response()->json(['cloned' => $rows->count()]);
    }

    private function assertBranch(?User $actor, Pricing $pricing): void
    {
        if (! $actor instanceof User) {
            abort(401);
        }

        if ($actor->role !== 'owner' && $pricing->branch_id !== $actor->branch_id) {
            abort(403);
        }
    }
}
