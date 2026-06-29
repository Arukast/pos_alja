<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialBranchStock extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'material_branch_stock';

    protected $fillable = [
        'material_id',
        'branch_id',
        'current_stock',
        'min_stock',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
