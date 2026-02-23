<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrewOutcome extends Model
{
    use HasUuids, BelongsToTenant;

    const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'vessel_id',
        'contract_id',
        'captain_candidate_id',
        'period_start',
        'period_end',
        'outcome_type',
        'severity',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'severity' => 'integer',
    ];

    // Outcome types
    const TYPE_EARLY_TERMINATION = 'early_termination';
    const TYPE_CONFLICT_REPORTED = 'conflict_reported';
    const TYPE_SAFETY_INCIDENT = 'safety_incident';
    const TYPE_PERFORMANCE_HIGH = 'performance_high';
    const TYPE_RETENTION_SUCCESS = 'retention_success';

    const VALID_TYPES = [
        self::TYPE_EARLY_TERMINATION,
        self::TYPE_CONFLICT_REPORTED,
        self::TYPE_SAFETY_INCIDENT,
        self::TYPE_PERFORMANCE_HIGH,
        self::TYPE_RETENTION_SUCCESS,
    ];

    // Positive vs negative classification for learning
    const POSITIVE_TYPES = [self::TYPE_PERFORMANCE_HIGH, self::TYPE_RETENTION_SUCCESS];
    const NEGATIVE_TYPES = [self::TYPE_EARLY_TERMINATION, self::TYPE_CONFLICT_REPORTED, self::TYPE_SAFETY_INCIDENT];

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(CandidateContract::class, 'contract_id');
    }

    public function captainCandidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'captain_candidate_id');
    }

    public function isPositive(): bool
    {
        return in_array($this->outcome_type, self::POSITIVE_TYPES);
    }

    public function isNegative(): bool
    {
        return in_array($this->outcome_type, self::NEGATIVE_TYPES);
    }
}
