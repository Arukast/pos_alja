<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Machine extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['branch_id', 'name'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
