<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateRiskSnapshot extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'pool_candidate_id',
        'computed_at',
        'fleet_type',
        'inputs_json',
        'outputs_json',
    ];

    protected $casts = [
        'computed_at' => 'datetime',
        'inputs_json' => 'array',
        'outputs_json' => 'array',
    ];

    public function poolCandidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class);
    }
}
