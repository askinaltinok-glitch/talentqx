<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAisVerification extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $table = 'contract_ais_verifications';

    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_FAILED = 'failed';
    const STATUS_NOT_APPLICABLE = 'not_applicable';

    protected $fillable = [
        'candidate_contract_id',
        'vessel_id',
        'status',
        'confidence_score',
        'reasons_json',
        'anomalies_json',
        'evidence_summary_json',
        'period_start',
        'period_end',
        'provider',
        'provider_request_id',
        'error_code',
        'error_message',
        'triggered_by',
        'triggered_by_user_id',
        'verified_at',
    ];

    protected $casts = [
        'confidence_score' => 'float',
        'reasons_json' => 'array',
        'anomalies_json' => 'array',
        'evidence_summary_json' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'verified_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(CandidateContract::class, 'candidate_contract_id');
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Scope: latest verification per contract (subquery approach).
     */
    public function scopeLatestPerContract($query)
    {
        return $query->whereIn('id', function ($sub) {
            $sub->selectRaw('(SELECT cav2.id FROM contract_ais_verifications cav2 WHERE cav2.candidate_contract_id = contract_ais_verifications.candidate_contract_id ORDER BY cav2.created_at DESC LIMIT 1)');
        });
    }
}
