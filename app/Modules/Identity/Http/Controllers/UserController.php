<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Identity\Http\Resources\UserResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewAny', User::class);

        $search = trim($request->string('search')->toString());

        $users = User::query()
            ->with('branch')
            ->when($actor->role === 'manager', function (Builder $query) use ($actor): void {
                $query->where('branch_id', $actor->branch_id)
                    ->whereIn('role', ['cashier', 'designer', 'production']);
            })
            ->when(
                $request->boolean('include_deleted'),
                fn (Builder $query) => $query->withTrashed()
            )
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when(
                $request->filled('role'),
                fn (Builder $query) => $query->where('role', $request->string('role')->toString())
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return UserResource::collection($users);
    }

    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        $user->loadMissing('branch');

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }
}
