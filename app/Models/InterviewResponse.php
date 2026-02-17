<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewResponse extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'interview_id',
        'question_id',
        'response_order',
        'video_segment_url',
        'audio_segment_url',
        'duration_seconds',
        'transcript',
        'transcript_confidence',
        'transcript_language',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'response_order' => 'integer',
        'duration_seconds' => 'integer',
        'transcript_confidence' => 'decimal:4',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    protected $attributes = [
        'transcript_language' => 'tr',
    ];

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(JobQuestion::class, 'question_id');
    }

    public function hasTranscript(): bool
    {
        return !empty($this->transcript);
    }

    public function getMediaUrl(): ?string
    {
        return $this->video_segment_url ?? $this->audio_segment_url;
    }
}
