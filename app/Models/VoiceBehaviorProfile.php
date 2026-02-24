<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceBehaviorProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'form_interview_id',
        'pool_candidate_id',
        'stress_index',
        'confidence_index',
        'decisiveness_index',
        'hesitation_index',
        'communication_clarity_index',
        'emotional_stability_index',
        'leadership_tone_index',
        'overall_voice_score',
        'computation_meta',
    ];

    protected $casts = [
        'stress_index' => 'float',
        'confidence_index' => 'float',
        'decisiveness_index' => 'float',
        'hesitation_index' => 'float',
        'communication_clarity_index' => 'float',
        'emotional_stability_index' => 'float',
        'leadership_tone_index' => 'float',
        'overall_voice_score' => 'float',
        'computation_meta' => 'array',
    ];

    public function formInterview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class);
    }

    public function poolCandidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class);
    }

    /**
     * Get all 7 indices as an associative array.
     */
    public function getIndicesArray(): array
    {
        return [
            'stress' => $this->stress_index,
            'confidence' => $this->confidence_index,
            'decisiveness' => $this->decisiveness_index,
            'hesitation' => $this->hesitation_index,
            'communication_clarity' => $this->communication_clarity_index,
            'emotional_stability' => $this->emotional_stability_index,
            'leadership_tone' => $this->leadership_tone_index,
        ];
    }
}
