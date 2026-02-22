<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateDecisionOverride extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'candidate_id',
        'decision',
        'reason',
        'created_by',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    const DECISION_APPROVE = 'approve';
    const DECISION_REVIEW = 'review';
    const DECISION_REJECT = 'reject';

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'candidate_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the currently active override for a candidate.
     * Active = latest row where expires_at is null or in the future.
     */
    public static function activeFor(string $candidateId): ?self
    {
        return static::where('candidate_id', $candidateId)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->first();
    }
}
