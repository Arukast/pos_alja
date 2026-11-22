<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;

final class DeleteProfile
{
    public function execute(User $user): void
    {
        $user->delete();
    }
}
