<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAccountIsActive
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if (! $user->is_active || $this->branchIsUnavailable($user)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Akun atau cabang Anda tidak aktif. Silakan hubungi pemilik.',
            ], 401);
        }

        return $next($request);
    }

    private function branchIsUnavailable(User $user): bool
    {
        if ($user->role === 'owner' || $user->branch_id === null) {
            return false;
        }

        $branch = $user->branch()->first();

        return $branch === null || ! $branch->is_active;
    }
}
