<?php

namespace App\Models;

use App\Models\Traits\IsDemoScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateNotification extends Model
{
    use HasUuids, IsDemoScoped;

    protected $table = 'candidate_notifications';

    public $timestamps = false;

    protected $fillable = [
        'pool_candidate_id', 'type', 'title', 'body',
        'data', 'tier_required', 'read_at', 'delivered_at', 'created_at',
        'is_demo',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    // Tier constants
    public const TIER_FREE = 'free';
    public const TIER_PLUS = 'plus';
    public const TIER_PRO = 'pro';

    public const TIERS = [self::TIER_FREE, self::TIER_PLUS, self::TIER_PRO];

    // Notification types
    public const TYPE_PROFILE_VIEWED = 'profile_viewed';
    public const TYPE_ROLE_VIEWED = 'role_viewed';
    public const TYPE_VESSEL_VIEWED = 'vessel_viewed';
    public const TYPE_STATUS_CHANGED = 'status_changed';
    public const TYPE_REVIEW_APPROVED = 'review_approved';
    public const TYPE_SHORTLISTED = 'shortlisted';
    public const TYPE_REJECTED = 'rejected';
    public const TYPE_HIRED = 'hired';
    public const TYPE_JOB_MATCH = 'job_match';
    public const TYPE_CERTIFICATE_EXPIRING = 'certificate_expiring';

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'pool_candidate_id');
    }

    public function markRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    // Scopes

    public function scopeForCandidate($query, string $candidateId)
    {
        return $query->where('pool_candidate_id', $candidateId);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForTier($query, string $tier)
    {
        // Free sees only free-tier, Plus sees free+plus, Pro sees all
        $allowed = match ($tier) {
            self::TIER_PRO => self::TIERS,
            self::TIER_PLUS => [self::TIER_FREE, self::TIER_PLUS],
            default => [self::TIER_FREE],
        };
        return $query->whereIn('tier_required', $allowed);
    }
}
