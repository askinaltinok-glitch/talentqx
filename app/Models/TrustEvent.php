<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustEvent extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'pool_candidate_id',
        'event_type',
        'payload_json',
    ];

    protected $casts = [
        'payload_json' => 'array',
    ];

    const TYPE_CONTRACT_ADDED = 'contract_added';
    const TYPE_CONTRACT_UPDATED = 'contract_updated';
    const TYPE_CONTRACT_DELETED = 'contract_deleted';
    const TYPE_CONTRACT_VERIFIED = 'contract_verified';
    const TYPE_RECOMPUTE = 'recompute';
    const TYPE_FLAG_TRIGGERED = 'flag_triggered';

    public function poolCandidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class);
    }
}
