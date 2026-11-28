<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\AttributeType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class UpdateAttributeType
{
    /**
     * @param  array{name: string, sort_order?: int|null, is_active?: bool}  $data
     */
    public function execute(AttributeType $type, array $data, User $actor): AttributeType
    {
        try {
            $updated = DB::transaction(function () use ($type, $data): AttributeType {
                $attributes = ['name' => $data['name']];

                if (($data['sort_order'] ?? null) !== null) {
                    $attributes['sort_order'] = (int) $data['sort_order'];
                }

                if (array_key_exists('is_active', $data)) {
                    $attributes['is_active'] = (bool) $data['is_active'];
                }

                $type->update($attributes);

                return $type;
            });
        } catch (Throwable $exception) {
            Log::error('Attribute type update failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'attribute_type_id' => $type->id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        return $updated->fresh() ?? $updated;
    }
}
