<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateScoringVector extends Model
{
    use HasUuids;

    protected $fillable = [
        'candidate_id',
        'technical_score',
        'behavioral_score',
        'reliability_score',
        'personality_score',
        'english_proficiency',
        'english_level',
        'english_weight',
        'composite_score',
        'vector_json',
        'computed_at',
        'version',
    ];

    protected $casts = [
        'technical_score' => 'decimal:2',
        'behavioral_score' => 'decimal:2',
        'reliability_score' => 'decimal:2',
        'personality_score' => 'decimal:2',
        'english_proficiency' => 'decimal:2',
        'english_weight' => 'decimal:2',
        'composite_score' => 'decimal:2',
        'vector_json' => 'array',
        'computed_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'candidate_id');
    }
}
