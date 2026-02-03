<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Candidate extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    public const VISIBILITY_CUSTOMER_ONLY = 'customer_only';
    public const VISIBILITY_MARKETPLACE_ANONYMOUS = 'marketplace_anonymous';

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Candidate $candidate) {
            if (empty($candidate->public_hash)) {
                $candidate->public_hash = Str::random(32);
            }
        });
    }

    protected $fillable = [
        'company_id',
        'branch_id',
        'job_id',
        'public_hash',
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
        'visibility_scope',
        'marketplace_consent',
        'marketplace_consent_at',
    ];

    protected $casts = [
        'cv_parsed_data' => 'array',
        'cv_match_score' => 'decimal:2',
        'tags' => 'array',
        'consent_given' => 'boolean',
        'consent_given_at' => 'datetime',
        'status_changed_at' => 'datetime',
        'marketplace_consent' => 'boolean',
        'marketplace_consent_at' => 'datetime',
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

    /**
     * Check if the candidate is visible in the marketplace.
     */
    public function isMarketplaceVisible(): bool
    {
        return $this->visibility_scope === self::VISIBILITY_MARKETPLACE_ANONYMOUS
            && $this->marketplace_consent === true;
    }

    /**
     * Enable marketplace visibility (requires consent).
     */
    public function enableMarketplaceVisibility(): void
    {
        $this->update([
            'visibility_scope' => self::VISIBILITY_MARKETPLACE_ANONYMOUS,
            'marketplace_consent' => true,
            'marketplace_consent_at' => now(),
        ]);
    }

    /**
     * Disable marketplace visibility.
     */
    public function disableMarketplaceVisibility(): void
    {
        $this->update([
            'visibility_scope' => self::VISIBILITY_CUSTOMER_ONLY,
            'marketplace_consent' => false,
            'marketplace_consent_at' => null,
        ]);
    }

    /**
     * Get the anonymous profile for marketplace display (NO PII).
     * Uses public_hash instead of actual ID for privacy.
     */
    public function getAnonymousProfile(): array
    {
        $analysis = $this->getLatestAnalysis();
        $cvData = $this->cv_parsed_data ?? [];

        return [
            'id' => $this->public_hash, // Use public_hash for anonymity
            'status' => $this->status,
            'cv_match_score' => $this->cv_match_score,
            'source' => $this->source,
            'created_at' => $this->created_at->toIso8601String(),

            // Skills and experience from CV (no PII)
            'skills' => $cvData['skills'] ?? [],
            'experience_years' => $cvData['experience_years'] ?? null,
            'education_level' => $cvData['education_level'] ?? null,

            // Job info (no company name in marketplace)
            'job_title' => $this->job?->title,
            'job_location' => $this->job?->location,

            // Analysis data
            'overall_score' => $analysis?->overall_score,
            'competency_scores' => $analysis?->competency_scores,
            'recommendation' => $analysis?->decision_snapshot['recommendation'] ?? null,
        ];
    }

    /**
     * Find candidate by public_hash.
     */
    public static function findByPublicHash(string $publicHash): ?self
    {
        return static::where('public_hash', $publicHash)->first();
    }

    /**
     * Find candidate by public_hash or fail.
     */
    public static function findByPublicHashOrFail(string $publicHash): self
    {
        return static::where('public_hash', $publicHash)->firstOrFail();
    }

    /**
     * Scope to filter candidates visible in marketplace.
     */
    public function scopeMarketplaceVisible($query)
    {
        return $query->where('visibility_scope', self::VISIBILITY_MARKETPLACE_ANONYMOUS)
            ->where('marketplace_consent', true);
    }

    /**
     * Get marketplace access requests for this candidate.
     */
    public function marketplaceAccessRequests(): HasMany
    {
        return $this->hasMany(MarketplaceAccessRequest::class);
    }
}
