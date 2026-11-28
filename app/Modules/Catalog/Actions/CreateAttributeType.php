<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\AttributeType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CreateAttributeType
{
    /**
     * @param  array{name: string, sort_order?: int|null, is_active?: bool}  $data
     */
    public function execute(array $data, User $actor): AttributeType
    {
        $base = Str::limit(Str::slug($data['name']), 100, '');

        if ($base === '') {
            throw ValidationException::withMessages([
                'name' => 'Nama tipe atribut tidak valid.',
            ]);
        }

        $sortOrder = ($data['sort_order'] ?? null) !== null
            ? (int) $data['sort_order']
            : min((int) AttributeType::max('sort_order') + 1, 2147483647);

        try {
            $type = DB::transaction(function () use ($base, $data, $sortOrder): AttributeType {
                $slug = $base;
                $suffix = 2;

                while (AttributeType::query()->where('slug', $slug)->exists()) {
                    $suffixText = '-'.$suffix;
                    $slug = Str::limit($base, 100 - strlen($suffixText), '').$suffixText;
                    $suffix++;
                }

                return AttributeType::create([
                    'name' => $data['name'],
                    'slug' => $slug,
                    'sort_order' => $sortOrder,
                    'is_active' => (bool) ($data['is_active'] ?? true),
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Attribute type creation failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        return $type->fresh() ?? $type;
    }
}
