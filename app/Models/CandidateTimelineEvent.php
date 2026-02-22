<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateTimelineEvent extends Model
{
    use HasUuids;

    protected $table = 'candidate_timeline_events';

    // Append-only: no updated_at
    public const UPDATED_AT = null;

    // Event types
    public const TYPE_APPLIED = 'applied';
    public const TYPE_INTERVIEW_STARTED = 'interview_started';
    public const TYPE_INTERVIEW_COMPLETED = 'interview_completed';
    public const TYPE_CREDENTIAL_UPLOADED = 'credential_uploaded';
    public const TYPE_CREDENTIAL_UPDATED = 'credential_updated';
    public const TYPE_REMINDER_SENT = 'reminder_sent';
    public const TYPE_COMPANY_FEEDBACK = 'company_feedback_received';
    public const TYPE_RATING_SUBMITTED = 'rating_submitted';
    public const TYPE_PROFILE_CREATED = 'profile_created';
    public const TYPE_PASSIVE_REGISTERED = 'passive_registered';

    // Sources
    public const SOURCE_PUBLIC = 'public_portal';
    public const SOURCE_HR = 'hr_portal';
    public const SOURCE_ADMIN = 'octo_admin';
    public const SOURCE_SYSTEM = 'system';

    // Event types safe to show in HR portal
    public const PORTAL_SAFE_TYPES = [
        self::TYPE_APPLIED,
        self::TYPE_INTERVIEW_STARTED,
        self::TYPE_INTERVIEW_COMPLETED,
        self::TYPE_CREDENTIAL_UPLOADED,
        self::TYPE_CREDENTIAL_UPDATED,
        self::TYPE_RATING_SUBMITTED,
    ];

    protected $fillable = [
        'pool_candidate_id',
        'event_type',
        'source',
        'payload_json',
    ];

    protected $casts = [
        'payload_json' => 'array',
    ];

    public function poolCandidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'pool_candidate_id');
    }

    /**
     * Create a timeline event.
     */
    public static function record(
        string $candidateId,
        string $eventType,
        string $source,
        ?array $payload = null
    ): self {
        return self::create([
            'pool_candidate_id' => $candidateId,
            'event_type' => $eventType,
            'source' => $source,
            'payload_json' => $payload,
        ]);
    }

    /**
     * Scope: portal-safe events only.
     */
    public function scopePortalSafe($query)
    {
        return $query->whereIn('event_type', self::PORTAL_SAFE_TYPES);
    }
}
