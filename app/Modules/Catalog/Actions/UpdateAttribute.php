<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\Attribute;
use App\Models\User;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class UpdateAttribute
{
    /**
     * @param  array{
     *     name: string,
     *     type: string,
     *     price: int|float|string,
     *     cost: int|float|string,
     *     is_active?: bool
     * }  $data
     */
    public function execute(Attribute $attribute, array $data, User $actor): Attribute
    {
        try {
            $updated = DB::transaction(function () use ($attribute, $data): Attribute {
                $attribute->update([
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'price' => $data['price'],
                    'cost' => $data['cost'],
                    'is_active' => array_key_exists('is_active', $data)
                        ? (bool) $data['is_active']
                        : (bool) $attribute->is_active,
                ]);

                return $attribute;
            });
        } catch (Throwable $exception) {
            Log::error('Attribute update failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'attribute_id' => $attribute->id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        SafeBroadcast::orderUpdated($actor->branch_id, $actor);

        return $updated->fresh('branch') ?? $updated->load('branch');
    }
}
