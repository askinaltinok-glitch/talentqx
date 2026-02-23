<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateQuestionAttempt extends Model
{
    use HasUuids;

    protected $fillable = [
        'candidate_id',
        'question_set_id',
        'attempt_no',
        'started_at',
        'completed_at',
        'selection_snapshot_json',
        'answers_json',
        'score_json',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'selection_snapshot_json' => 'array',
        'answers_json' => 'array',
        'score_json' => 'array',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'candidate_id');
    }

    public function questionSet(): BelongsTo
    {
        return $this->belongsTo(InterviewQuestionSet::class, 'question_set_id');
    }
}
