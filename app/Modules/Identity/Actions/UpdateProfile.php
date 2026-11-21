<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;

final class UpdateProfile
{
    /**
     * @param  array{name: string, email: string}  $data
     */
    public function execute(User $user, array $data): User
    {
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user->loadMissing('branch');
    }
}
