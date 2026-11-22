<?php

declare(strict_types=1);

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
 * @property string|float $cost
 * @property-read Branch|null $branch
 */
class Machine extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['branch_id', 'name', 'cost'];

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
