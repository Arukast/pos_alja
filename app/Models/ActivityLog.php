<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'user_id',
        'username',
        'role',
        'action', // CREATE, UPDATE, DELETE, etc.
        'description',
        'model_type',
        'model_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
