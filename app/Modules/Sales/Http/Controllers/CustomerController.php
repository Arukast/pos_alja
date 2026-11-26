<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Modules\Sales\Actions\CreateCustomer;
use App\Modules\Sales\Http\Requests\StoreCustomerRequest;
use App\Modules\Sales\Http\Resources\CustomerResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Customer::class);

        $search = trim($request->string('search')->toString());

        $customers = Customer::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(8)
            ->get();

        return CustomerResource::collection($customers);
    }

    public function store(
        StoreCustomerRequest $request,
        CreateCustomer $action
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $customer = $action->execute($request->validated(), $actor);

        return response()->json([
            'customer' => new CustomerResource($customer),
        ], 201);
    }
}
