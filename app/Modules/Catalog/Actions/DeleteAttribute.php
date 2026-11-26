<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\Attribute;
use App\Models\User;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DeleteAttribute
{
    public function execute(Attribute $attribute, User $actor): void
    {
        try {
            DB::transaction(function () use ($attribute): void {
                $attribute->delete();
            });
        } catch (Throwable $exception) {
            Log::error('Attribute deletion failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'attribute_id' => $attribute->id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        SafeBroadcast::orderUpdated($actor->branch_id, $actor);
    }
}
