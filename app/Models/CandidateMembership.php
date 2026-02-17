<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateMembership extends Model
{
    use HasUuids;

    protected $table = 'candidate_memberships';

    protected $fillable = [
        'pool_candidate_id',
        'tier',
        'started_at',
        'expires_at',
        'meta',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    public const TIER_FREE = 'free';
    public const TIER_PLUS = 'plus';
    public const TIER_PRO = 'pro';
    public const TIER_ENTERPRISE = 'enterprise';

    public const TIERS = [self::TIER_FREE, self::TIER_PLUS, self::TIER_PRO, self::TIER_ENTERPRISE];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'pool_candidate_id');
    }

    public function isActive(): bool
    {
        if ($this->expires_at === null) {
            return true;
        }

        return $this->expires_at->isFuture();
    }

    public function getEffectiveTier(): string
    {
        if (!$this->isActive()) {
            return self::TIER_FREE;
        }

        return $this->tier;
    }
}
