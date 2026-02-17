<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentResult extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'session_id',
        'status',
        'ai_model',
        'input_tokens',
        'output_tokens',
        'cost_usd',
        'cost_limited',
        'used_prompt_version_id',
        'analyzed_at',
        'overall_score',
        'competency_scores',
        'risk_flags',
        'risk_level',
        'level_label',
        'level_numeric',
        'development_plan',
        'strengths',
        'improvement_areas',
        'promotion_suitable',
        'promotion_readiness',
        'promotion_notes',
        'cheating_risk_score',
        'cheating_level',
        'cheating_flags',
        'raw_ai_response',
        'validation_errors',
        'retry_count',
        'fallback_model',
        'question_analyses',
    ];

    protected $casts = [
        'analyzed_at' => 'datetime',
        'overall_score' => 'decimal:2',
        'competency_scores' => 'array',
        'risk_flags' => 'array',
        'level_numeric' => 'integer',
        'development_plan' => 'array',
        'strengths' => 'array',
        'improvement_areas' => 'array',
        'promotion_suitable' => 'boolean',
        'raw_ai_response' => 'array',
        'question_analyses' => 'array',
        'validation_errors' => 'array',
        'retry_count' => 'integer',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cost_usd' => 'decimal:6',
        'cost_limited' => 'boolean',
        'cheating_risk_score' => 'integer',
        'cheating_flags' => 'array',
    ];

    const STATUS_COMPLETED = 'completed';
    const STATUS_ANALYSIS_FAILED = 'analysis_failed';
    const STATUS_PENDING_RETRY = 'pending_retry';

    public function session(): BelongsTo
    {
        return $this->belongsTo(AssessmentSession::class, 'session_id');
    }

    public function getCompetencyScore(string $code): ?float
    {
        return $this->competency_scores[$code]['score'] ?? null;
    }

    public function getCompetencyFeedback(string $code): ?string
    {
        return $this->competency_scores[$code]['feedback'] ?? null;
    }

    public function hasRiskFlags(): bool
    {
        return !empty($this->risk_flags);
    }

    public function getRiskFlagCount(): int
    {
        return count($this->risk_flags ?? []);
    }

    public function getCriticalRiskFlags(): array
    {
        return collect($this->risk_flags)
            ->where('severity', 'critical')
            ->values()
            ->toArray();
    }

    public function getHighPriorityDevelopmentAreas(): array
    {
        return collect($this->development_plan)
            ->where('priority', 'high')
            ->values()
            ->toArray();
    }

    public function isPromotionReady(): bool
    {
        return in_array($this->promotion_readiness, ['ready', 'highly_ready']);
    }

    public function getLevelColor(): string
    {
        return match($this->level_numeric) {
            1 => 'red',
            2 => 'orange',
            3 => 'yellow',
            4 => 'blue',
            5 => 'green',
            default => 'gray',
        };
    }

    public function getRiskColor(): string
    {
        return match($this->risk_level) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }

    public function scopePromotable($query)
    {
        return $query->where('promotion_suitable', true);
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('level_label', $level);
    }

    public function scopeAnalysisFailed($query)
    {
        return $query->where('status', self::STATUS_ANALYSIS_FAILED);
    }

    public function scopeCostLimited($query)
    {
        return $query->where('cost_limited', true);
    }

    public function scopeHighCheatingRisk($query, int $threshold = 70)
    {
        return $query->where('cheating_risk_score', '>=', $threshold);
    }

    /**
     * Get the prompt version used for this analysis
     */
    public function promptVersion(): BelongsTo
    {
        return $this->belongsTo(PromptVersion::class, 'used_prompt_version_id');
    }

    /**
     * Check if analysis failed
     */
    public function isAnalysisFailed(): bool
    {
        return $this->status === self::STATUS_ANALYSIS_FAILED;
    }

    /**
     * Check if cost was limited
     */
    public function wasCostLimited(): bool
    {
        return $this->cost_limited === true;
    }

    /**
     * Get total tokens used
     */
    public function getTotalTokens(): int
    {
        return ($this->input_tokens ?? 0) + ($this->output_tokens ?? 0);
    }

    /**
     * Check if cheating risk is high
     */
    public function hasHighCheatingRisk(): bool
    {
        return $this->cheating_level === 'high' || ($this->cheating_risk_score ?? 0) >= 70;
    }

    /**
     * Get cheating flag count
     */
    public function getCheatingFlagCount(): int
    {
        return count($this->cheating_flags ?? []);
    }

    /**
     * Get cheating risk color
     */
    public function getCheatingRiskColor(): string
    {
        return match($this->cheating_level) {
            'high' => 'red',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }
}
