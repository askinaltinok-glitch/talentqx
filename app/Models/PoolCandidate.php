<?php

namespace App\Models;

use App\Models\Traits\IsDemoScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * PoolCandidate - Candidate Supply Engine
 *
 * Represents a candidate in the talent pool system.
 * Separate from the ATS Candidate model (which is company-specific).
 *
 * This is the core entity of the Candidate Supply Engine:
 * - Candidates enter via self-assessment (form interviews)
 * - After assessment, qualified candidates enter the pool
 * - Pool candidates can be presented to companies
 */
class PoolCandidate extends Model
{
    use HasUuids, IsDemoScoped;

    protected $table = 'pool_candidates';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'country_code',
        'preferred_language',
        'english_level_self',
        'source_channel',
        'source_meta',
        'candidate_source',
        'status',
        'primary_industry',
        'seafarer',
        'english_assessment_required',
        'video_assessment_required',
        'last_assessed_at',
        'is_demo',
    ];

    protected $casts = [
        'source_meta' => 'array',
        'seafarer' => 'boolean',
        'is_demo' => 'boolean',
        'english_assessment_required' => 'boolean',
        'video_assessment_required' => 'boolean',
        'last_assessed_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_NEW = 'new';
    public const STATUS_ASSESSED = 'assessed';
    public const STATUS_IN_POOL = 'in_pool';
    public const STATUS_PRESENTED = 'presented_to_company';
    public const STATUS_HIRED = 'hired';
    public const STATUS_ARCHIVED = 'archived';

    // Source channel constants
    public const SOURCE_LINKEDIN = 'linkedin';
    public const SOURCE_REFERRAL = 'referral';
    public const SOURCE_MARITIME_EVENT = 'maritime_event';
    public const SOURCE_JOB_BOARD = 'job_board';
    public const SOURCE_ORGANIC = 'organic';
    public const SOURCE_COMPANY_INVITE = 'company_invite';

    public const SOURCE_CHANNELS = [
        self::SOURCE_LINKEDIN,
        self::SOURCE_REFERRAL,
        self::SOURCE_MARITIME_EVENT,
        self::SOURCE_JOB_BOARD,
        self::SOURCE_ORGANIC,
        self::SOURCE_COMPANY_INVITE,
    ];

    // Industry constants
    public const INDUSTRY_GENERAL = 'general';
    public const INDUSTRY_MARITIME = 'maritime';
    public const INDUSTRY_RETAIL = 'retail';
    public const INDUSTRY_LOGISTICS = 'logistics';
    public const INDUSTRY_HOSPITALITY = 'hospitality';

    public const INDUSTRIES = [
        self::INDUSTRY_GENERAL,
        self::INDUSTRY_MARITIME,
        self::INDUSTRY_RETAIL,
        self::INDUSTRY_LOGISTICS,
        self::INDUSTRY_HOSPITALITY,
    ];

    // English levels
    public const ENGLISH_LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    // Candidate source constants
    public const CANDIDATE_SOURCE_PUBLIC = 'public_portal';
    public const CANDIDATE_SOURCE_COMPANY = 'company_upload';
    public const CANDIDATE_SOURCE_ADMIN = 'octo_admin';
    public const CANDIDATE_SOURCE_IMPORT = 'import';

    /**
     * Get candidate profile (master profile with consents).
     */
    public function profile(): HasOne
    {
        return $this->hasOne(CandidateProfile::class, 'pool_candidate_id');
    }

    /**
     * Get contact points.
     */
    public function contactPoints(): HasMany
    {
        return $this->hasMany(CandidateContactPoint::class, 'pool_candidate_id');
    }

    /**
     * Get primary email contact point.
     */
    public function primaryEmail(): HasOne
    {
        return $this->hasOne(CandidateContactPoint::class, 'pool_candidate_id')
            ->where('type', CandidateContactPoint::TYPE_EMAIL)
            ->where('is_primary', true);
    }

    /**
     * Get primary phone contact point.
     */
    public function primaryPhone(): HasOne
    {
        return $this->hasOne(CandidateContactPoint::class, 'pool_candidate_id')
            ->where('type', CandidateContactPoint::TYPE_PHONE)
            ->where('is_primary', true);
    }

    /**
     * Get credentials (credential wallet).
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(CandidateCredential::class, 'pool_candidate_id');
    }

    /**
     * Get timeline events.
     */
    public function timelineEvents(): HasMany
    {
        return $this->hasMany(CandidateTimelineEvent::class, 'pool_candidate_id')
            ->orderByDesc('created_at');
    }

    /**
     * Get reminder logs.
     */
    public function reminderLogs(): HasMany
    {
        return $this->hasMany(CandidateReminderLog::class, 'pool_candidate_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(CandidateContract::class, 'pool_candidate_id')->orderBy('start_date');
    }

    public function trustProfile(): HasOne
    {
        return $this->hasOne(CandidateTrustProfile::class, 'pool_candidate_id');
    }

    public function trustEvents(): HasMany
    {
        return $this->hasMany(TrustEvent::class, 'pool_candidate_id');
    }

    public function seaTimeLogs(): HasMany
    {
        return $this->hasMany(SeaTimeLog::class, 'pool_candidate_id');
    }

    /**
     * Ensure a CandidateProfile exists for this candidate.
     */
    public function ensureProfile(string $status = CandidateProfile::STATUS_SEEKER, ?string $language = null): CandidateProfile
    {
        return $this->profile ?? CandidateProfile::create([
            'pool_candidate_id' => $this->id,
            'status' => $status,
            'preferred_language' => $language ?? $this->preferred_language ?? 'en',
            'data_processing_consent_at' => now(),
        ]);
    }

    /**
     * Ensure primary email contact point exists.
     */
    public function ensurePrimaryEmail(): ?CandidateContactPoint
    {
        if (!$this->email) {
            return null;
        }

        return CandidateContactPoint::firstOrCreate(
            ['type' => CandidateContactPoint::TYPE_EMAIL, 'value' => $this->email],
            [
                'pool_candidate_id' => $this->id,
                'is_primary' => true,
                'is_verified' => false,
            ]
        );
    }

    /**
     * Check if candidate's primary email is verified.
     */
    public function hasPrimaryEmailVerified(): bool
    {
        $primary = $this->primaryEmail;
        return $primary !== null && $primary->is_verified;
    }

    /**
     * Scope: visible to companies (seeker + consented, not blocked).
     */
    public function scopeVisibleToCompany($query)
    {
        return $query->whereHas('profile', function ($q) {
            $q->where('status', CandidateProfile::STATUS_SEEKER)
              ->whereNotNull('data_processing_consent_at');
        });
    }

    /**
     * Get all form interviews for this candidate.
     */
    public function formInterviews(): HasMany
    {
        return $this->hasMany(FormInterview::class, 'pool_candidate_id')
            ->orderByDesc('created_at');
    }

    /**
     * Get all seafarer certificates for this candidate.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(SeafarerCertificate::class, 'pool_candidate_id')
            ->orderBy('certificate_type');
    }

    /**
     * Get all profile views for this candidate.
     */
    public function profileViews(): HasMany
    {
        return $this->hasMany(CandidateProfileView::class, 'pool_candidate_id')
            ->orderByDesc('viewed_at');
    }

    /**
     * Get all notifications for this candidate.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(CandidateNotification::class, 'pool_candidate_id')
            ->orderByDesc('created_at');
    }

    /**
     * Get all vessel reviews by this candidate.
     */
    public function vesselReviews(): HasMany
    {
        return $this->hasMany(VesselReview::class, 'pool_candidate_id')
            ->orderByDesc('created_at');
    }

    /**
     * Get membership.
     */
    public function membership(): HasOne
    {
        return $this->hasOne(CandidateMembership::class, 'pool_candidate_id');
    }

    /**
     * Get push tokens.
     */
    public function pushTokens(): HasMany
    {
        return $this->hasMany(CandidatePushToken::class, 'pool_candidate_id');
    }

    /**
     * Get job applications.
     */
    public function jobApplications(): HasMany
    {
        return $this->hasMany(MaritimeJobApplication::class, 'pool_candidate_id');
    }

    /**
     * Get effective membership tier.
     */
    public function getMembershipTierAttribute(): string
    {
        $membership = $this->membership;
        if (!$membership) {
            return 'free';
        }
        return $membership->getEffectiveTier();
    }

    /**
     * Get all presentations to companies.
     */
    public function presentations(): HasMany
    {
        return $this->hasMany(CandidatePresentation::class)
            ->orderByDesc('presented_at');
    }

    /**
     * Check if candidate has been presented to any company.
     */
    public function hasBeenPresented(): bool
    {
        return $this->presentations()->exists();
    }

    /**
     * Get presentation count.
     */
    public function getPresentationCountAttribute(): int
    {
        return $this->presentations()->count();
    }

    /**
     * Get the latest completed interview.
     */
    public function latestCompletedInterview(): ?FormInterview
    {
        return $this->formInterviews()
            ->where('status', FormInterview::STATUS_COMPLETED)
            ->first();
    }

    /**
     * Get the latest interview score.
     */
    public function getLatestScoreAttribute(): ?float
    {
        $interview = $this->latestCompletedInterview();
        return $interview?->calibrated_score ?? $interview?->final_score;
    }

    /**
     * Get the latest decision.
     */
    public function getLatestDecisionAttribute(): ?string
    {
        return $this->latestCompletedInterview()?->decision;
    }

    /**
     * Get the latest risk flags.
     */
    public function getLatestRiskFlagsAttribute(): ?array
    {
        return $this->latestCompletedInterview()?->risk_flags;
    }

    /**
     * Get full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Check if candidate is in pool.
     */
    public function isInPool(): bool
    {
        return $this->status === self::STATUS_IN_POOL;
    }

    /**
     * Check if candidate is a seafarer (maritime).
     */
    public function isSeafarer(): bool
    {
        return $this->seafarer === true;
    }

    /**
     * Move candidate to pool after successful assessment.
     */
    public function moveToPool(string $industry): void
    {
        $this->update([
            'status' => self::STATUS_IN_POOL,
            'primary_industry' => $industry,
            'last_assessed_at' => now(),
        ]);
    }

    /**
     * Mark as assessed (but not pooled - rejected).
     */
    public function markAsAssessed(): void
    {
        $this->update([
            'status' => self::STATUS_ASSESSED,
            'last_assessed_at' => now(),
        ]);
    }

    /**
     * Mark as presented to company.
     */
    public function markAsPresented(): void
    {
        $this->update([
            'status' => self::STATUS_PRESENTED,
        ]);
    }

    /**
     * Mark as hired.
     */
    public function markAsHired(): void
    {
        $this->update([
            'status' => self::STATUS_HIRED,
        ]);
    }

    /**
     * Archive candidate.
     */
    public function archive(): void
    {
        $this->update([
            'status' => self::STATUS_ARCHIVED,
        ]);
    }

    /**
     * Set maritime-specific flags.
     */
    public function setMaritimeFlags(): void
    {
        $this->update([
            'seafarer' => true,
            'english_assessment_required' => true,
            'video_assessment_required' => true,
        ]);
    }

    /**
     * Scope: filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: filter by industry.
     */
    public function scopeIndustry($query, string $industry)
    {
        return $query->where('primary_industry', $industry);
    }

    /**
     * Scope: filter by source channel.
     */
    public function scopeSourceChannel($query, string $channel)
    {
        return $query->where('source_channel', $channel);
    }

    /**
     * Scope: candidates in pool.
     */
    public function scopeInPool($query)
    {
        return $query->where('status', self::STATUS_IN_POOL);
    }

    /**
     * Scope: seafarers only.
     */
    public function scopeSeafarers($query)
    {
        return $query->where('seafarer', true);
    }

    /**
     * Scope: filter by english level.
     */
    public function scopeEnglishLevel($query, string $level)
    {
        return $query->where('english_level_self', $level);
    }

    /**
     * Scope: minimum english level.
     */
    public function scopeMinEnglishLevel($query, string $minLevel)
    {
        $levels = self::ENGLISH_LEVELS;
        $minIndex = array_search($minLevel, $levels);

        if ($minIndex === false) {
            return $query;
        }

        $acceptableLevels = array_slice($levels, $minIndex);
        return $query->whereIn('english_level_self', $acceptableLevels);
    }
}
