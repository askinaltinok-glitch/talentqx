<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewSessionAnalysis extends Model
{
    use HasUuids;

    protected $table = 'interview_session_analyses';

    protected $fillable = [
        'session_id',
        'ai_model',
        'ai_model_version',
        'prompt_version',
        'analyzed_at',
        'processing_time_ms',
        'overall_score',
        'recommendation',
        'confidence_percent',
        'dimension_scores',
        'behavior_analysis',
        'question_analyses',
        'strengths',
        'improvement_areas',
        'interview_notes',
        'summary_text',
        'hr_recommendations',
        'raw_ai_response',
        'tokens_used',
        'cost_usd',
        'status',
        'error_message',
    ];

    protected $casts = [
        'overall_score' => 'decimal:2',
        'confidence_percent' => 'integer',
        'processing_time_ms' => 'integer',
        'tokens_used' => 'integer',
        'cost_usd' => 'decimal:6',
        'dimension_scores' => 'array',
        'question_analyses' => 'array',
        'behavior_analysis' => 'array',
        'strengths' => 'array',
        'improvement_areas' => 'array',
        'analyzed_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class, 'session_id');
    }

    /**
     * Get score for a specific dimension
     */
    public function getDimensionScore(string $dimension): ?float
    {
        return $this->dimension_scores[$dimension]['score'] ?? null;
    }

    /**
     * Check if there are any risk flags
     */
    public function hasRiskFlags(): bool
    {
        return !empty($this->getRedFlagsAttribute());
    }

    /**
     * Get fit level based on overall score
     */
    public function getFitLevel(): string
    {
        if ($this->overall_score >= 75) {
            return 'strong_fit';
        }
        if ($this->overall_score >= 60) {
            return 'potential_fit';
        }
        return 'weak_fit';
    }

    /**
     * Get red flags from behavior analysis
     */
    public function getRedFlagsAttribute(): array
    {
        $flags = [];
        $behavior = $this->behavior_analysis ?? [];

        // Extract risk flags from behavior analysis
        if (isset($behavior['risk_indicators'])) {
            foreach ($behavior['risk_indicators'] as $indicator) {
                $flags[] = [
                    'type' => 'behavior',
                    'severity' => $indicator['severity'] ?? 'medium',
                    'description' => $indicator['description'] ?? '',
                ];
            }
        }

        return $flags;
    }
}
