<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateTrustProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'pool_candidate_id',
        'cri_score',
        'confidence_level',
        'short_contract_ratio',
        'overlap_count',
        'gap_months_total',
        'unique_company_count_3y',
        'rank_anomaly_flag',
        'frequent_switch_flag',
        'timeline_inconsistency_flag',
        'flags_json',
        'detail_json',
        'stability_index',
        'risk_score',
        'risk_tier',
        'compliance_score',
        'compliance_status',
        'compliance_computed_at',
        'competency_score',
        'competency_status',
        'competency_computed_at',
        'computed_at',
    ];

    protected $casts = [
        'cri_score' => 'float',
        'short_contract_ratio' => 'float',
        'overlap_count' => 'integer',
        'gap_months_total' => 'integer',
        'unique_company_count_3y' => 'integer',
        'rank_anomaly_flag' => 'boolean',
        'frequent_switch_flag' => 'boolean',
        'timeline_inconsistency_flag' => 'boolean',
        'flags_json' => 'array',
        'detail_json' => 'array',
        'stability_index' => 'float',
        'risk_score' => 'float',
        'compliance_score' => 'integer',
        'compliance_computed_at' => 'datetime',
        'competency_score' => 'integer',
        'competency_computed_at' => 'datetime',
        'computed_at' => 'datetime',
    ];

    const CONFIDENCE_LOW = 'low';
    const CONFIDENCE_MEDIUM = 'medium';
    const CONFIDENCE_HIGH = 'high';

    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    const RISK_CRITICAL = 'critical';

    const COMPLIANCE_COMPLIANT = 'compliant';
    const COMPLIANCE_NEEDS_REVIEW = 'needs_review';
    const COMPLIANCE_NOT_COMPLIANT = 'not_compliant';

    const COMPETENCY_STRONG = 'strong';
    const COMPETENCY_MODERATE = 'moderate';
    const COMPETENCY_WEAK = 'weak';

    public function poolCandidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class);
    }

    /**
     * Generate human-readable risk notes from stored flags.
     */
    public function getRiskNotesAttribute(): array
    {
        $notes = [];

        if ($this->short_contract_ratio > 0.6) {
            $notes[] = 'High ratio of short contracts';
        }

        if ($this->overlap_count > 0) {
            $notes[] = "{$this->overlap_count} timeline overlap(s) found";
        } else {
            $notes[] = 'No timeline overlap';
        }

        if ($this->gap_months_total > 18) {
            $notes[] = "Long career gaps ({$this->gap_months_total} months total)";
        }

        if ($this->rank_anomaly_flag) {
            $notes[] = 'Rank progression anomaly detected';
        } else {
            $notes[] = 'Stable rank progression';
        }

        if ($this->frequent_switch_flag) {
            $notes[] = 'Frequent company switching pattern';
        }

        return $notes;
    }
}
