<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewAnswer extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_id',
        'question_id',
        'question_key',
        'audio_path',
        'raw_text',
        'processed_text',
        'duration_seconds',
        'metadata',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'metadata' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class, 'session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(InterviewQuestion::class, 'question_id');
    }
}
