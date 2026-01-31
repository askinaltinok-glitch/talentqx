<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponseSimilarity extends Model
{
    use HasUuids;

    protected $fillable = [
        'response_id_a',
        'response_id_b',
        'job_id',
        'question_order',
        'cosine_similarity',
        'jaccard_similarity',
        'flagged',
    ];

    protected $casts = [
        'cosine_similarity' => 'decimal:4',
        'jaccard_similarity' => 'decimal:4',
        'flagged' => 'boolean',
    ];

    public function responseA(): BelongsTo
    {
        return $this->belongsTo(InterviewResponse::class, 'response_id_a');
    }

    public function responseB(): BelongsTo
    {
        return $this->belongsTo(InterviewResponse::class, 'response_id_b');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function scopeFlagged($query)
    {
        return $query->where('flagged', true);
    }

    public function scopeHighSimilarity($query, float $threshold = 0.85)
    {
        return $query->where('cosine_similarity', '>=', $threshold);
    }
}
