<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Resources;

use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Backup */
final class BackupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $localPath = $this->local_path;

        $localAvailable = is_string($localPath)
            && $localPath !== ''
            && Storage::disk((string) config('backup.disks.local'))->exists($localPath);

        $meta = $this->meta;

        if (is_array($meta)) {
            foreach (array_keys($meta) as $key) {
                if (str_starts_with((string) $key, 'pending_')) {
                    unset($meta[$key]);
                }
            }
        }

        return [
            'id' => $this->id,
            'trigger' => $this->trigger,
            'destination' => $this->destination,
            'status' => $this->status,
            'filename' => $this->filename,
            'size_bytes' => $this->size_bytes,
            'local_available' => $localAvailable,
            'remote_available' => $this->remote_path !== null,
            'is_pinned' => (bool) $this->is_pinned,
            'error' => $this->error,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'created_at' => $this->created_at,
            'meta' => $meta,
        ];
    }
}
