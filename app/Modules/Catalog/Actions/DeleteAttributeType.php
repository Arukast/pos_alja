<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\AttributeType;
use App\Models\User;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DeleteAttributeType
{
    public function execute(AttributeType $attributeType, User $actor): void
    {
        try {
            DB::transaction(function () use ($attributeType): void {
                $attributeType->delete();
            });
        } catch (Throwable $exception) {
            Log::error('Attribute type deletion failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'attribute_type_id' => $attributeType->id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        SafeBroadcast::orderUpdated($actor->branch_id, $actor);
    }
}
