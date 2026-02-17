<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaritimeJob extends Model
{
    use HasUuids;

    protected $table = 'maritime_jobs';

    protected $fillable = [
        'pool_company_id',
        'vessel_type',
        'rank',
        'salary_range',
        'contract_length',
        'rotation',
        'internet_policy',
        'bonus_policy',
        'description',
        'is_active',
        'operation_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const OPERATION_TYPES = ['sea', 'river'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(PoolCompany::class, 'pool_company_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(MaritimeJobApplication::class, 'maritime_job_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRank($query, string $rank)
    {
        return $query->where('rank', $rank);
    }

    public function scopeByVesselType($query, string $vesselType)
    {
        return $query->where('vessel_type', $vesselType);
    }
}
