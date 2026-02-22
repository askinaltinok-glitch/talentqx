<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LanguageAssessment extends Model
{
    use HasUuids;

    protected $fillable = [
        'candidate_id',
        'assessment_language',
        'declared_level',
        'declared_confidence',
        'mcq_score',
        'mcq_total',
        'mcq_correct',
        'selected_questions',
        'writing_score',
        'writing_rubric',
        'writing_text',
        'interview_score',
        'interview_evidence',
        'estimated_level',
        'confidence',
        'overall_score',
        'signals',
        'locked_level',
        'locked_by',
        'locked_at',
        'retake_count',
        'last_test_at',
    ];

    protected $casts = [
        'writing_rubric' => 'array',
        'interview_evidence' => 'array',
        'selected_questions' => 'array',
        'signals' => 'array',
        'confidence' => 'float',
        'mcq_score' => 'integer',
        'mcq_total' => 'integer',
        'mcq_correct' => 'integer',
        'writing_score' => 'integer',
        'interview_score' => 'integer',
        'overall_score' => 'integer',
        'declared_confidence' => 'integer',
        'retake_count' => 'integer',
        'locked_at' => 'datetime',
        'last_test_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'candidate_id');
    }

    public static function forCandidate(string $candidateId): ?self
    {
        return static::where('candidate_id', $candidateId)->first();
    }
}
