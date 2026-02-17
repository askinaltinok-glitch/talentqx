<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewAnalysis extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'interview_analyses';

    protected $fillable = [
        'interview_id',
        'ai_model',
        'ai_model_version',
        'analyzed_at',
        'competency_scores',
        'overall_score',
        'behavior_analysis',
        'red_flag_analysis',
        'culture_fit',
        'decision_snapshot',
        'raw_ai_response',
        'question_analyses',
        // Anti-cheat fields
        'cheating_risk_score',
        'cheating_flags',
        'cheating_level',
        'timing_analysis',
        'similarity_analysis',
        'consistency_analysis',
    ];

    protected $casts = [
        'analyzed_at' => 'datetime',
        'competency_scores' => 'array',
        'overall_score' => 'decimal:2',
        'behavior_analysis' => 'array',
        'red_flag_analysis' => 'array',
        'culture_fit' => 'array',
        'decision_snapshot' => 'array',
        'raw_ai_response' => 'array',
        'question_analyses' => 'array',
        // Anti-cheat casts
        'cheating_risk_score' => 'decimal:2',
        'cheating_flags' => 'array',
        'timing_analysis' => 'array',
        'similarity_analysis' => 'array',
        'consistency_analysis' => 'array',
    ];

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    public function getRecommendation(): ?string
    {
        return $this->decision_snapshot['recommendation'] ?? null;
    }

    public function getConfidencePercent(): ?int
    {
        return $this->decision_snapshot['confidence_percent'] ?? null;
    }

    public function hasRedFlags(): bool
    {
        return ($this->red_flag_analysis['flags_detected'] ?? false) === true;
    }

    public function getRedFlagsCount(): int
    {
        return count($this->red_flag_analysis['flags'] ?? []);
    }

    public function getCompetencyScore(string $code): ?float
    {
        return $this->competency_scores[$code]['score'] ?? null;
    }

    public function getCultureFitScore(): ?float
    {
        return $this->culture_fit['overall_fit'] ?? null;
    }

    public function getReasons(): array
    {
        return $this->decision_snapshot['reasons'] ?? [];
    }

    public function getSuggestedQuestions(): array
    {
        return $this->decision_snapshot['suggested_questions'] ?? [];
    }

    // Anti-cheat helpers
    public function hasCheatingRisk(): bool
    {
        return $this->cheating_level === 'high' || $this->cheating_level === 'medium';
    }

    public function getCheatingFlagsCount(): int
    {
        return count($this->cheating_flags ?? []);
    }

    public function isHighCheatingRisk(): bool
    {
        return $this->cheating_level === 'high';
    }
}
