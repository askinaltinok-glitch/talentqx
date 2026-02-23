<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewInvitation extends Model
{
    use HasUuids;

    protected $table = 'interview_invitations';

    public const STATUS_INVITED   = 'invited';
    public const STATUS_STARTED   = 'started';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED   = 'expired';

    public const EXPIRY_HOURS = 48;

    protected $fillable = [
        'pool_candidate_id',
        'form_interview_id',
        'invitation_token',
        'invitation_token_hash',
        'status',
        'invited_at',
        'expires_at',
        'started_at',
        'completed_at',
        'expired_at',
        'access_count',
        'ip_started',
        'locale',
        'meta',
    ];

    protected $hidden = [
        'invitation_token',
        'invitation_token_hash',
    ];

    protected $casts = [
        'invited_at'   => 'datetime',
        'expires_at'   => 'datetime',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'expired_at'   => 'datetime',
        'meta'         => 'array',
        'access_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $invitation) {
            if (empty($invitation->invitation_token)) {
                $token = bin2hex(random_bytes(32));
                $invitation->invitation_token = $token;
                $invitation->invitation_token_hash = hash('sha256', $token);
            }

            if (empty($invitation->status)) {
                $invitation->status = self::STATUS_INVITED;
            }

            if (empty($invitation->invited_at)) {
                $invitation->invited_at = now();
            }

            if (empty($invitation->expires_at)) {
                $invitation->expires_at = ($invitation->invited_at ?? now())->copy()->addHours(self::EXPIRY_HOURS);
            }
        });
    }

    // ── Relationships ──

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'pool_candidate_id');
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class, 'form_interview_id');
    }

    // ── State Machine ──

    public function isAccessible(): bool
    {
        return $this->status === self::STATUS_INVITED && now()->lt($this->expires_at);
    }

    public function canResume(): bool
    {
        return $this->status === self::STATUS_STARTED && now()->lt($this->expires_at);
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || (in_array($this->status, [self::STATUS_INVITED, self::STATUS_STARTED]) && now()->gte($this->expires_at));
    }

    public function markStarted(string $ip): void
    {
        if ($this->status !== self::STATUS_INVITED) {
            throw new \RuntimeException("Cannot start invitation in status: {$this->status}");
        }

        $this->update([
            'status'       => self::STATUS_STARTED,
            'started_at'   => now(),
            'ip_started'   => $ip,
            'access_count' => $this->access_count + 1,
        ]);
    }

    public function markCompleted(): void
    {
        if ($this->status !== self::STATUS_STARTED) {
            throw new \RuntimeException("Cannot complete invitation in status: {$this->status}");
        }

        $this->update([
            'status'       => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markExpired(): void
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return; // Already terminal — no-op
        }

        $this->update([
            'status'     => self::STATUS_EXPIRED,
            'expired_at' => now(),
        ]);
    }

    // ── Query Helpers ──

    public static function findByTokenHash(string $token): ?self
    {
        $hash = hash('sha256', $token);

        return static::where('invitation_token_hash', $hash)->first();
    }
}
