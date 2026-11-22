<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string|null $branch_id
 * @property string $name
 * @property string $type
 * @property-read Branch|null $branch
 */
class Attribute extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'name',
        'type', // finishing, cut, display
    ];

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
