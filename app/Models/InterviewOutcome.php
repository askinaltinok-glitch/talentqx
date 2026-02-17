<?php

namespace App\Models;

use App\Models\Traits\IsDemoScoped;
use App\Services\ML\LearningService;
use App\Services\ML\MlLearningService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class InterviewOutcome extends Model
{
    use HasUuids, IsDemoScoped;

    protected $fillable = [
        'outcome_score',
        'form_interview_id',
        'hired',
        'started',
        'still_employed_30d',
        'still_employed_90d',
        'performance_rating',
        'incident_flag',
        'incident_notes',
        'outcome_source',
        'recorded_by',
        'recorded_at',
        'notes',
        'is_demo',
    ];

    protected $casts = [
        'hired' => 'boolean',
        'started' => 'boolean',
        'still_employed_30d' => 'boolean',
        'still_employed_90d' => 'boolean',
        'performance_rating' => 'integer',
        'incident_flag' => 'boolean',
        'recorded_at' => 'datetime',
        'outcome_score' => 'integer',
        'is_demo' => 'boolean',
    ];

    /**
     * Boot the model and register event listeners.
     */
    protected static function booted(): void
    {
        // Trigger ML learning when outcome is saved
        static::saved(function (InterviewOutcome $outcome) {
            // Skip ML learning for demo records
            if ($outcome->is_demo) {
                Log::channel('single')->info('InterviewOutcome::saved: Skipping ML learning for demo', ['id' => $outcome->id]);
                return;
            }
            // Calculate and store outcome_score if not set
            if ($outcome->outcome_score === null) {
                $outcome->updateQuietly(['outcome_score' => $outcome->getOutcomeScore()]);
            }

            // 1. Gradient-based weight learning (MlLearningService)
            try {
                $mlLearningService = app(MlLearningService::class);
                $result = $mlLearningService->updateWeightsFromOutcome($outcome);

                if ($result['success'] && isset($result['error'])) {
                    Log::channel('single')->info('ML Gradient Learning triggered', [
                        'outcome_id' => $outcome->id,
                        'interview_id' => $outcome->form_interview_id,
                        'error' => $result['error'],
                    ]);
                }
            } catch (\Throwable $e) {
                Log::channel('single')->warning('ML Gradient Learning failed', [
                    'outcome_id' => $outcome->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // 2. Pattern-based learning (LearningService)
            try {
                $learningService = app(LearningService::class);
                $patternResult = $learningService->learnFromOutcome($outcome);

                if ($patternResult['learned']) {
                    Log::channel('single')->info('ML Pattern Learning triggered', [
                        'outcome_id' => $outcome->id,
                        'is_fp' => $patternResult['is_false_positive'] ?? false,
                        'is_fn' => $patternResult['is_false_negative'] ?? false,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::channel('single')->warning('ML Pattern Learning failed', [
                    'outcome_id' => $outcome->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Valid outcome sources
     */
    public const SOURCE_ADMIN = 'admin';
    public const SOURCE_CLIENT = 'client';
    public const SOURCE_SELF = 'self';
    public const SOURCE_API = 'api';

    public function formInterview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class);
    }

    /**
     * Check if outcome indicates success (hired + retained 30d)
     */
    public function isSuccessful(): bool
    {
        return $this->hired === true
            && $this->started === true
            && $this->still_employed_30d === true;
    }

    /**
     * Check if outcome indicates strong success (retained 90d + no incidents)
     */
    public function isStrongSuccess(): bool
    {
        return $this->isSuccessful()
            && $this->still_employed_90d === true
            && $this->incident_flag !== true;
    }

    /**
     * Calculate outcome score for calibration purposes
     * Returns 0-100 where:
     * - Not hired: 0
     * - Hired but didn't start: 10
     * - Started but left <30d: 30
     * - 30d retained: 50
     * - 90d retained: 70
     * - 90d retained + no incidents: 85
     * - 90d + no incidents + good performance: 100
     */
    public function getOutcomeScore(): int
    {
        if ($this->hired !== true) {
            return 0;
        }

        if ($this->started !== true) {
            return 10;
        }

        if ($this->still_employed_30d !== true) {
            return 30;
        }

        $score = 50;

        if ($this->still_employed_90d === true) {
            $score = 70;

            if ($this->incident_flag !== true) {
                $score = 85;

                if ($this->performance_rating !== null && $this->performance_rating >= 4) {
                    $score = 100;
                }
            }
        }

        return $score;
    }
}
