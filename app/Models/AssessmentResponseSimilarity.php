<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentResponseSimilarity extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'session_a_id',
        'session_b_id',
        'question_order',
        'similarity_score',
        'similarity_type',
        'flagged',
    ];

    protected $casts = [
        'question_order' => 'integer',
        'similarity_score' => 'decimal:2',
        'flagged' => 'boolean',
    ];

    public function sessionA(): BelongsTo
    {
        return $this->belongsTo(AssessmentSession::class, 'session_a_id');
    }

    public function sessionB(): BelongsTo
    {
        return $this->belongsTo(AssessmentSession::class, 'session_b_id');
    }

    public function isCritical(): bool
    {
        return $this->similarity_score >= 90;
    }

    public function isHigh(): bool
    {
        return $this->similarity_score >= 80 && $this->similarity_score < 90;
    }

    public function scopeFlagged($query)
    {
        return $query->where('flagged', true);
    }

    public function scopeHighSimilarity($query, float $threshold = 85)
    {
        return $query->where('similarity_score', '>=', $threshold);
    }
}
