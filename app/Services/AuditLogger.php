<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Single write path for the append-only activity trail.
 */
final class AuditLogger
{
    /**
     * Payload keys whose values must never reach the audit trail.
     *
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'remember_token',
        'token',
        'secret',
        'access_key',
        'secret_key',
    ];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(
        ?User $actor,
        string $action,
        string $description,
        ?Model $subject = null,
        array $payload = [],
        ?string $branchId = null,
    ): ActivityLog {
        return ActivityLog::create([
            'branch_id' => $branchId ?? $actor?->branch_id,
            'user_id' => $actor?->id,
            'username' => $actor === null ? 'system' : $actor->username,
            'role' => $actor === null ? 'system' : $actor->role,
            'action' => $action,
            'description' => Str::limit($description, 255, ''),
            'model_type' => $subject !== null ? $subject::class : null,
            'model_id' => $subject?->getKey(),
            'payload' => $this->scrub($payload),
        ]);
    }

    /**
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    private function scrub(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                unset($payload[$key]);

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->scrub($value);
            }
        }

        return $payload;
    }
}
