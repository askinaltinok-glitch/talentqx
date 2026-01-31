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
        'overall_score',
        'dimension_scores',
        'question_analyses',
        'behavior_analysis',
        'risk_flags',
        'summary',
        'recommendations',
        'raw_response',
        'model_version',
        'analyzed_at',
    ];

    protected $casts = [
        'overall_score' => 'decimal:2',
        'dimension_scores' => 'array',
        'question_analyses' => 'array',
        'behavior_analysis' => 'array',
        'risk_flags' => 'array',
        'recommendations' => 'array',
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
        return !empty($this->risk_flags);
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
}
