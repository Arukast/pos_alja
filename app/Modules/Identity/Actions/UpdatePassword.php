<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class UpdatePassword
{
    /**
     * @param  array{password: string}  $data
     */
    public function execute(User $user, array $data): void
    {
        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();
    }
}
