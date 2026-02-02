<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Candidate extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'branch_id',
        'job_id',
        'email',
        'phone',
        'first_name',
        'last_name',
        'cv_url',
        'cv_parsed_data',
        'cv_match_score',
        'source',
        'referrer_name',
        'status',
        'status_changed_at',
        'status_changed_by',
        'status_note',
        'consent_given',
        'consent_version',
        'consent_given_at',
        'consent_ip',
        'internal_notes',
        'tags',
    ];

    protected $casts = [
        'cv_parsed_data' => 'array',
        'cv_match_score' => 'decimal:2',
        'tags' => 'array',
        'consent_given' => 'boolean',
        'consent_given_at' => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'applied',
    ];

    public const STATUS_APPLIED = 'applied';
    public const STATUS_INTERVIEW_PENDING = 'interview_pending';
    public const STATUS_INTERVIEW_COMPLETED = 'interview_completed';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_SHORTLISTED = 'shortlisted';
    public const STATUS_HIRED = 'hired';
    public const STATUS_REJECTED = 'rejected';

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    public function latestInterview(): HasOne
    {
        return $this->hasOne(Interview::class)->latestOfMany();
    }

    public function consentLogs(): HasMany
    {
        return $this->hasMany(ConsentLog::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function updateStatus(string $status, ?string $note = null, ?User $user = null): void
    {
        $this->update([
            'status' => $status,
            'status_note' => $note,
            'status_changed_at' => now(),
            'status_changed_by' => $user?->id,
        ]);
    }

    public function hasCompletedInterview(): bool
    {
        return $this->interviews()
            ->where('status', Interview::STATUS_COMPLETED)
            ->exists();
    }

    public function getLatestAnalysis(): ?InterviewAnalysis
    {
        return $this->latestInterview?->analysis;
    }
}
