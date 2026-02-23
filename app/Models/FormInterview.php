<?php

namespace App\Models;

use App\Exceptions\ImmutableRecordException;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\IsDemoScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FormInterview extends Model
{
    use HasUuids, IsDemoScoped, BelongsToTenant;

    protected $fillable = [
        // Candidate Supply Engine link
        'pool_candidate_id',
        'interview_invitation_id',
        'company_id',
        'acquisition_source_snapshot',
        'acquisition_campaign_snapshot',
        // Core fields
        'version',
        'language',
        'position_code',
        'template_position_code',
        'industry_code',
        'platform_code',
        'brand_domain',
        'status',
        'template_json',
        'template_json_sha256',
        'meta',
        'competency_scores',
        'risk_flags',
        'final_score',
        'decision',
        'decision_reason',
        'completed_at',
        'anonymized_at',
        // Calibration fields
        'raw_final_score',
        'raw_decision',
        'raw_decision_reason',
        'position_mean_score',
        'position_std_dev_score',
        'z_score',
        'calibrated_score',
        'calibration_version',
        // Policy fields
        'policy_code',
        'policy_version',
        // Admin-only mutable field
        'admin_notes',
        // Assessment fields (Maritime)
        'english_assessment_status',
        'english_assessment_score',
        'video_assessment_status',
        'video_assessment_url',
        'is_demo',
        // Maritime Decision Engine output
        'decision_summary_json',
        // Command Engine v2
        'type',
        'command_class_detected',
        'command_profile_id',
        'interview_phase',
        'phase1_completed_at',
        'capability_profile_json',
        'deployment_packet_json',
        // Resolver Engine v2
        'phase',
        'needs_review',
        'resolver_status',
        'linked_phase_interview_id',
        'scenario_set_json',
        'override_class',
        'override_by_user_id',
        // Behavioral Engine v1
        'behavioral_profile_id',
    ];

    protected $casts = [
        'acquisition_campaign_snapshot' => 'array',
        'is_demo' => 'boolean',
        'meta' => 'array',
        'competency_scores' => 'array',
        'risk_flags' => 'array',
        'completed_at' => 'datetime',
        'anonymized_at' => 'datetime',
        // Calibration casts
        'position_mean_score' => 'float',
        'position_std_dev_score' => 'float',
        'z_score' => 'float',
        'decision_summary_json' => 'array',
        'phase1_completed_at' => 'datetime',
        'interview_phase' => 'integer',
        'capability_profile_json' => 'array',
        'deployment_packet_json' => 'array',
        // Resolver Engine v2 casts
        'phase' => 'integer',
        'needs_review' => 'boolean',
        'scenario_set_json' => 'array',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    /**
     * Fields that are locked after interview completion.
     * These cannot be modified once status=completed.
     */
    protected const LOCKED_FIELDS = [
        'version',
        'language',
        'position_code',
        'template_position_code',
        'industry_code',
        'template_json',
        'template_json_sha256',
        'meta',
        'competency_scores',
        'risk_flags',
        'final_score',
        'decision',
        'decision_reason',
        'completed_at',
        'raw_final_score',
        'raw_decision',
        'raw_decision_reason',
        'position_mean_score',
        'position_std_dev_score',
        'z_score',
        'calibrated_score',
        'calibration_version',
        'policy_code',
        'policy_version',
    ];

    /**
     * Fields that admins can modify even after completion.
     */
    protected const ADMIN_MUTABLE_FIELDS = [
        'admin_notes',
    ];

    protected static function booted(): void
    {
        static::updating(function (FormInterview $interview) {
            // Only enforce immutability if the record WAS completed before this update
            if ($interview->getOriginal('status') !== self::STATUS_COMPLETED) {
                return;
            }

            // Check for locked field modifications
            $dirty = $interview->getDirty();
            $lockedChanges = array_intersect(array_keys($dirty), self::LOCKED_FIELDS);

            if (!empty($lockedChanges)) {
                throw new ImmutableRecordException(
                    "Cannot modify completed interview. Locked fields: " . implode(', ', $lockedChanges),
                    $interview->id,
                    $lockedChanges
                );
            }
        });
    }

    /**
     * Get the pool candidate (Candidate Supply Engine).
     */
    public function poolCandidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(FormInterviewAnswer::class)->orderBy('slot');
    }

    public function outcome(): HasOne
    {
        return $this->hasOne(InterviewOutcome::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(CandidateConsent::class);
    }

    public function modelFeature(): HasOne
    {
        return $this->hasOne(ModelFeature::class);
    }

    public function modelPredictions(): HasMany
    {
        return $this->hasMany(ModelPrediction::class);
    }

    public function capabilityScore(): HasOne
    {
        return $this->hasOne(CapabilityScore::class);
    }

    public function resolverAuditLogs(): HasMany
    {
        return $this->hasMany(ResolverAuditLog::class);
    }

    public function scenarioResponses(): HasMany
    {
        return $this->hasMany(MaritimeScenarioResponse::class)->orderBy('slot');
    }

    public function linkedPhaseInterview(): BelongsTo
    {
        return $this->belongsTo(self::class, 'linked_phase_interview_id');
    }

    public function phase2Interview(): HasOne
    {
        return $this->hasOne(self::class, 'linked_phase_interview_id');
    }

    public function behavioralProfile(): BelongsTo
    {
        return $this->belongsTo(BehavioralProfile::class, 'behavioral_profile_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }
}
