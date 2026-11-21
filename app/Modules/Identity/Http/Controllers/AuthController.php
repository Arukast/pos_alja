<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Identity\Actions\AttemptLogin;
use App\Modules\Identity\Actions\DeleteProfile;
use App\Modules\Identity\Actions\UpdatePassword;
use App\Modules\Identity\Actions\UpdateProfile;
use App\Modules\Identity\Http\Requests\ConfirmPasswordRequest;
use App\Modules\Identity\Http\Requests\DeleteProfileRequest;
use App\Modules\Identity\Http\Requests\LoginRequest;
use App\Modules\Identity\Http\Requests\UpdatePasswordRequest;
use App\Modules\Identity\Http\Requests\UpdateProfileRequest;
use App\Modules\Identity\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    public function login(LoginRequest $request, AttemptLogin $action): JsonResponse
    {
        $user = $action->execute($request->validated());

        $request->session()->regenerate();

        return response()->json([
            'user' => new UserResource($user->loadMissing('branch')),
        ]);
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return response()->json([
            'user' => new UserResource($user->loadMissing('branch')),
        ]);
    }

    public function updateProfile(
        UpdateProfileRequest $request,
        UpdateProfile $action
    ): JsonResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $updated = $action->execute($user, $request->validated());

        return response()->json([
            'user' => new UserResource($updated),
        ]);
    }

    public function updatePassword(
        UpdatePasswordRequest $request,
        UpdatePassword $action
    ): Response {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $action->execute($user, $request->validated());

        return response()->noContent();
    }

    public function confirmPassword(ConfirmPasswordRequest $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! Hash::check((string) $request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return response()->noContent();
    }

    public function destroy(DeleteProfileRequest $request, DeleteProfile $action): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $action->execute($user);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
