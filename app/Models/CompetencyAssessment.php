<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetencyAssessment extends Model
{
    use HasUuids;

    protected $fillable = [
        'pool_candidate_id',
        'form_interview_id',
        'computed_at',
        'score_total',
        'score_by_dimension',
        'flags',
        'evidence_summary',
        'answer_scores',
        'created_by',
    ];

    protected $casts = [
        'computed_at' => 'datetime',
        'score_total' => 'float',
        'score_by_dimension' => 'array',
        'flags' => 'array',
        'evidence_summary' => 'array',
        'answer_scores' => 'array',
    ];

    public function poolCandidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class);
    }

    public function formInterview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class);
    }
}
