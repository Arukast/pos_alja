<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityLog */
final class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch' => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ] : null,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null,
            'username' => $this->username,
            'role' => $this->role,
            'action' => $this->action,
            'description' => $this->description,
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
            'payload' => $this->payload,
            'created_at' => $this->created_at,
        ];
    }
}
