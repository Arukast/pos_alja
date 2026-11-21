<?php

declare(strict_types=1);

namespace App\Support;

use App\Events\OrderUpdated;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SafeBroadcast
{
    public static function orderUpdated(?string $branchId, User $actor): void
    {
        try {
            event(new OrderUpdated($branchId));
        } catch (Throwable $exception) {
            Log::error('OrderUpdated broadcast failed', [
                'user_id' => $actor->id,
                'branch_id' => $branchId,
                'exception' => $exception,
            ]);
        }
    }
}
