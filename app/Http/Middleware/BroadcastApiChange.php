<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\SafeBroadcast;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class BroadcastApiChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $actor = $request->user();

        if (
            $actor instanceof User
            && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)
            && $response->getStatusCode() < 400
        ) {
            $branchId = $actor->branch_id ?? $request->input('branch_id');
            $branchId = is_string($branchId) && Str::isUuid($branchId) ? $branchId : null;

            SafeBroadcast::orderUpdated($branchId, $actor);
        }

        return $response;
    }
}
