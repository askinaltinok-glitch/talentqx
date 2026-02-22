<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AisVerification extends Model
{
    use HasUuids;

    protected $fillable = [
        'candidate_contract_id', 'vessel_id', 'status',
        'confidence_score', 'failure_reason',
        'triggered_by_user_id', 'verified_at',
    ];

    protected $casts = [
        'confidence_score' => 'float',
        'verified_at' => 'datetime',
    ];

    const STATUS_NOT_APPLICABLE = 'not_applicable';
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_FAILED = 'failed';

    public function contract(): BelongsTo
    {
        return $this->belongsTo(CandidateContract::class, 'candidate_contract_id');
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }
}
