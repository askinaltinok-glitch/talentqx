<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Interview extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'candidate_id',
        'job_id',
        'access_token',
        'token_expires_at',
        'status',
        'started_at',
        'completed_at',
        'video_url',
        'audio_url',
        'video_duration_seconds',
        'device_info',
        'ip_address',
        'browser_info',
        // Email tracking
        'invitation_sent_at',
        'reminder_sent_at',
        'last_hour_reminder_sent_at',
        'completion_email_sent_at',
        'company_notified_at',
        'scheduled_at',
        // Email verification
        'email_verification_code_hash',
        'email_verification_expires_at',
        'email_verification_attempts',
        'email_verified_at',
        // Timing / punctuality
        'joined_at',
        'late_minutes',
        'no_show_marked_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'device_info' => 'array',
        'video_duration_seconds' => 'integer',
        // Email tracking
        'invitation_sent_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'last_hour_reminder_sent_at' => 'datetime',
        'completion_email_sent_at' => 'datetime',
        'company_notified_at' => 'datetime',
        'scheduled_at' => 'datetime',
        // Email verification
        'email_verification_expires_at' => 'datetime',
        'email_verification_attempts' => 'integer',
        'email_verified_at' => 'datetime',
        // Timing / punctuality
        'joined_at' => 'datetime',
        'late_minutes' => 'integer',
        'no_show_marked_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($interview) {
            if (empty($interview->access_token)) {
                $interview->access_token = Str::random(64);
            }
            if (empty($interview->token_expires_at)) {
                $interview->token_expires_at = now()->addHours(
                    (int) config('interview.token_expiry_hours', 72)
                );
            }
        });
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(InterviewResponse::class)->orderBy('response_order');
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(InterviewAnalysis::class);
    }

    public function isTokenValid(): bool
    {
        return $this->token_expires_at->isFuture() &&
               in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    public function start(array $deviceInfo = [], ?string $ip = null): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'device_info' => $deviceInfo,
            'ip_address' => $ip,
        ]);

        $this->candidate->updateStatus(Candidate::STATUS_INTERVIEW_PENDING);
    }

    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $this->candidate->updateStatus(Candidate::STATUS_INTERVIEW_COMPLETED);
    }

    public function getDurationInMinutes(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInMinutes($this->completed_at);
    }

    public function getInterviewUrl(): string
    {
        return config('app.url') . '/i/' . $this->access_token;
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    public static function findByToken(string $token): ?self
    {
        return static::where('access_token', $token)->first();
    }
}
