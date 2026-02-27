<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormInterviewAnalysis extends Model
{
    use HasUuids;

    protected $table = 'form_interview_analyses';

    protected $fillable = [
        'form_interview_id',
        'ai_model',
        'ai_provider',
        'analyzed_at',
        'competency_scores',
        'overall_score',
        'behavior_analysis',
        'red_flag_analysis',
        'culture_fit',
        'decision_snapshot',
        'raw_ai_response',
        'question_analyses',
        'scoring_method',
        'token_usage_input',
        'token_usage_output',
        'latency_ms',
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
    ];

    public function formInterview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class);
    }

    public function getRecommendation(): ?string
    {
        return $this->decision_snapshot['recommendation'] ?? null;
    }

    public function hasRedFlags(): bool
    {
        return ($this->red_flag_analysis['flags_detected'] ?? false) === true;
    }

    public function getConfidencePercent(): ?int
    {
        return $this->decision_snapshot['confidence_percent'] ?? null;
    }
}
