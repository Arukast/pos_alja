<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\Attribute;
use App\Models\User;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CreateAttribute
{
    /**
     * @param  array{
     *     name: string,
     *     type: string,
     *     branch_id?: string|null,
     *     price: int|float|string,
     *     cost: int|float|string,
     *     is_active?: bool
     * }  $data
     */
    public function execute(array $data, User $actor): Attribute
    {
        if ($actor->role !== 'owner'
            && array_key_exists('branch_id', $data)
            && $data['branch_id'] !== null
            && $data['branch_id'] !== $actor->branch_id) {
            abort(403);
        }

        $branchId = $actor->role === 'owner'
            ? ($data['branch_id'] ?? null)
            : $actor->branch_id;

        try {
            $attribute = DB::transaction(fn (): Attribute => Attribute::create([
                'name' => $data['name'],
                'type' => $data['type'],
                'branch_id' => $branchId,
                'price' => $data['price'],
                'cost' => $data['cost'],
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]));
        } catch (Throwable $exception) {
            Log::error('Attribute creation failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        SafeBroadcast::orderUpdated($actor->branch_id, $actor);

        return $attribute->load('branch');
    }
}
