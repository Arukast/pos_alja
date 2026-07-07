<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Machine extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['branch_id', 'name', 'cost'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
