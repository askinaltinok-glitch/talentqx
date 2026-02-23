<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaptainProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'candidate_id',
        'style_vector_json',
        'command_profile_json',
        'evidence_counts_json',
        'confidence',
        'last_computed_at',
    ];

    protected $casts = [
        'style_vector_json' => 'array',
        'command_profile_json' => 'array',
        'evidence_counts_json' => 'array',
        'confidence' => 'float',
        'last_computed_at' => 'datetime',
    ];

    // Style trait keys
    const TRAIT_AUTHORITY = 'authority';
    const TRAIT_DISCIPLINE = 'discipline';
    const TRAIT_COACHING = 'coaching';
    const TRAIT_TEAM_ORIENTATION = 'team_orientation';
    const TRAIT_PROCEDURAL = 'procedural';
    const TRAIT_EMPATHY = 'empathy';

    // Command profile labels
    const LABEL_AUTHORITATIVE = 'Authoritative';
    const LABEL_COLLABORATIVE = 'Collaborative';
    const LABEL_PROCEDURAL = 'Procedural';
    const LABEL_ADAPTIVE = 'Adaptive';
    const LABEL_HIGH_DISCIPLINE = 'High Discipline';
    const LABEL_COACHING = 'Coaching-Oriented';

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'candidate_id');
    }

    /**
     * Get the primary command style label.
     */
    public function getPrimaryStyleAttribute(): string
    {
        $labels = $this->command_profile_json['labels'] ?? [];
        return $labels[0] ?? 'Unknown';
    }

    /**
     * Check if the profile has sufficient confidence for synergy scoring.
     */
    public function isSufficientForScoring(): bool
    {
        return $this->confidence >= 0.40;
    }
}
