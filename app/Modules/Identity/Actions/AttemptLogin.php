<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AttemptLogin
{
    /**
     * @param  array{username: string, password: string, remember?: bool}  $credentials
     */
    public function execute(array $credentials): User
    {
        $this->ensureIsNotRateLimited($credentials['username']);

        if (! Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ], (bool) ($credentials['remember'] ?? false))) {
            RateLimiter::hit($this->throttleKey($credentials['username']));

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($credentials['username']));

        $user = Auth::user();

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        return $user;
    }

    private function ensureIsNotRateLimited(string $username): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($username), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey($username));

        abort(429, trans('auth.throttle', [
            'seconds' => $seconds,
            'minutes' => ceil($seconds / 60),
        ]));
    }

    private function throttleKey(string $username): string
    {
        return Str::transliterate(Str::lower($username).'|'.request()->ip());
    }
}
