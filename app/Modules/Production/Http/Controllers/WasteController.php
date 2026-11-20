<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WastedMaterial;
use App\Modules\Inventory\Queries\WasteList;
use App\Modules\Production\Http\Resources\WasteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class WasteController extends Controller
{
    public function index(Request $request, WasteList $waste): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewAny', WastedMaterial::class);

        return WasteResource::collection($waste->paginate($actor));
    }

    public function options(Request $request, WasteList $waste): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewAny', WastedMaterial::class);

        return response()->json($waste->options($actor));
    }
}
