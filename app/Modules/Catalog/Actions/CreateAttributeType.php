<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\AttributeType;
use App\Models\User;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CreateAttributeType
{
    /**
     * @param  array{name: string, calculation_archetype: string}  $data
     */
    public function execute(array $data, User $actor): AttributeType
    {
        try {
            $attributeType = DB::transaction(fn (): AttributeType => AttributeType::create([
                'name' => $data['name'],
                'calculation_archetype' => $data['calculation_archetype'],
            ]));
        } catch (Throwable $exception) {
            Log::error('Attribute type creation failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        SafeBroadcast::orderUpdated($actor->branch_id, $actor);

        return $attributeType;
    }
}
