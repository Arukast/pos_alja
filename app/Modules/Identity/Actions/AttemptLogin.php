<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AttemptLogin
{
    private const DUMMY_HASH = '$2y$12$ne./1kPCkI1WLo44tzQ4u.rvJ9DI4ALuVDHFF3Fd3zlicMT0jBvdi';

    /**
     * @param  array{username: string, password: string, remember?: bool}  $credentials
     */
    public function execute(array $credentials): User
    {
        $this->ensureIsNotRateLimited($credentials['username']);

        $user = User::query()
            ->where('username', $credentials['username'])
            ->first();

        if ($user === null) {
            // Equalize bcrypt work so a missing username does not answer faster.
            Hash::check($credentials['password'], self::DUMMY_HASH);
            $this->fail($credentials['username']);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            $this->fail($credentials['username']);
        }

        if (! $user->is_active) {
            $this->fail($credentials['username']);
        }

        if ($user->role !== 'owner' && $user->branch_id !== null) {
            $branch = $user->branch()->first();

            if ($branch === null || ! $branch->is_active) {
                $this->fail($credentials['username']);
            }
        }

        RateLimiter::clear($this->throttleKey($credentials['username']));

        Auth::login($user, (bool) ($credentials['remember'] ?? false));

        return $user;
    }

    private function fail(string $username): never
    {
        RateLimiter::hit($this->throttleKey($username));

        throw ValidationException::withMessages([
            'username' => trans('auth.failed'),
        ]);
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
