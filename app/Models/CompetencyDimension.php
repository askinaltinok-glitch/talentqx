<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetencyDimension extends Model
{
    use HasUuids;

    protected $fillable = [
        'code',
        'department',
        'description',
        'weight_default',
        'is_active',
    ];

    protected $casts = [
        'description' => 'array',
        'weight_default' => 'float',
        'is_active' => 'boolean',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(CompetencyQuestion::class, 'dimension_id');
    }
}
