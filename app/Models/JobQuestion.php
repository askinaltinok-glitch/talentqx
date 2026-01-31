<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobQuestion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'job_id',
        'question_order',
        'question_type',
        'question_text',
        'question_text_tts',
        'competency_code',
        'ideal_answer_points',
        'scoring_rubric',
        'time_limit_seconds',
        'is_required',
    ];

    protected $casts = [
        'ideal_answer_points' => 'array',
        'scoring_rubric' => 'array',
        'question_order' => 'integer',
        'time_limit_seconds' => 'integer',
        'is_required' => 'boolean',
    ];

    protected $attributes = [
        'time_limit_seconds' => 180,
        'is_required' => true,
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(InterviewResponse::class, 'question_id');
    }

    public function getCompetency(): ?array
    {
        if (!$this->competency_code) {
            return null;
        }

        return $this->job?->getEffectiveCompetencies()
            ? collect($this->job->getEffectiveCompetencies())
                ->firstWhere('code', $this->competency_code)
            : null;
    }

    public function getEffectiveScoringRubric(): array
    {
        return $this->scoring_rubric ?? $this->job?->getEffectiveScoringRubric() ?? [];
    }
}
