<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'trigger',
        'destination',
        'status',
        'filename',
        'local_path',
        'remote_path',
        'size_bytes',
        'error',
        'is_pinned',
        'meta',
        'user_id',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'is_pinned' => 'boolean',
        'meta' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
