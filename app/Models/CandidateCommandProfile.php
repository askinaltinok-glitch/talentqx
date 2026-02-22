<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateCommandProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'candidate_id',
        'raw_identity_answers',
        'vessel_experience',
        'dwt_history',
        'automation_exposure',
        'cargo_history',
        'trading_areas',
        'crew_scale_history',
        'incident_history',
        'derived_command_class',
        'confidence_score',
        'identity_confidence_score',
        'multi_class_flags',
        'source',
        'completeness_pct',
        'generated_at',
    ];

    protected $casts = [
        'raw_identity_answers' => 'array',
        'vessel_experience' => 'array',
        'dwt_history' => 'array',
        'automation_exposure' => 'array',
        'cargo_history' => 'array',
        'trading_areas' => 'array',
        'crew_scale_history' => 'array',
        'incident_history' => 'array',
        'multi_class_flags' => 'array',
        'confidence_score' => 'float',
        'identity_confidence_score' => 'float',
        'completeness_pct' => 'integer',
        'generated_at' => 'datetime',
    ];

    // ── Relationships ──

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function commandClass(): BelongsTo
    {
        return $this->belongsTo(CommandClass::class, 'derived_command_class', 'code');
    }

    // ── Scopes ──

    public function scopeForCandidate($query, string $candidateId)
    {
        return $query->where('candidate_id', $candidateId);
    }

    public function scopeByClass($query, string $classCode)
    {
        return $query->where('derived_command_class', $classCode);
    }

    public function scopeHighConfidence($query, float $min = 60.0)
    {
        return $query->where('confidence_score', '>=', $min);
    }

    public function scopeByVesselType($query, string $vesselType)
    {
        return $query->whereJsonContains('vessel_experience', [['vessel_type' => $vesselType]]);
    }

    public function scopeByCargoType($query, string $cargoType)
    {
        return $query->whereJsonContains('cargo_history', $cargoType);
    }

    public function scopeByDwtRange($query, int $minDwt, int $maxDwt)
    {
        return $query->whereRaw(
            "JSON_EXTRACT(dwt_history, '$.max') >= ? AND JSON_EXTRACT(dwt_history, '$.min') <= ?",
            [$minDwt, $maxDwt]
        );
    }

    // ── Accessors ──

    /**
     * Get flat list of vessel types from experience entries.
     */
    public function getVesselTypes(): array
    {
        if (empty($this->vessel_experience)) {
            return [];
        }

        return array_unique(array_column($this->vessel_experience, 'vessel_type'));
    }

    /**
     * Get DWT range from history.
     */
    public function getDwtMin(): ?int
    {
        return $this->dwt_history['min'] ?? null;
    }

    public function getDwtMax(): ?int
    {
        return $this->dwt_history['max'] ?? null;
    }

    /**
     * Get crew scale range from history.
     */
    public function getCrewMin(): ?int
    {
        return $this->crew_scale_history['min'] ?? null;
    }

    public function getCrewMax(): ?int
    {
        return $this->crew_scale_history['max'] ?? null;
    }

    /**
     * Get automation level keywords from exposure.
     */
    public function getAutomationLevels(): array
    {
        if (empty($this->automation_exposure)) {
            return [];
        }

        return $this->automation_exposure['levels'] ?? [];
    }

    /**
     * Check if profile has multi-class ambiguity.
     */
    public function isMultiClass(): bool
    {
        return !empty($this->multi_class_flags);
    }

    /**
     * Get the latest profile for a candidate.
     */
    public static function latestForCandidate(string $candidateId): ?self
    {
        return static::forCandidate($candidateId)
            ->orderByDesc('created_at')
            ->first();
    }
}
