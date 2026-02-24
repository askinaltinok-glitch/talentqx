<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceBehavioralSignal extends Model
{
    use HasUuids;

    protected $fillable = [
        'form_interview_id',
        'question_slot',
        'utterance_count',
        'total_word_count',
        'total_duration_s',
        'avg_confidence',
        'min_confidence',
        'avg_wpm',
        'total_pause_count',
        'total_long_pause_count',
        'total_filler_count',
        'avg_filler_ratio',
        'utterance_signals_json',
    ];

    protected $casts = [
        'question_slot' => 'integer',
        'utterance_count' => 'integer',
        'total_word_count' => 'integer',
        'total_duration_s' => 'float',
        'avg_confidence' => 'float',
        'min_confidence' => 'float',
        'avg_wpm' => 'float',
        'total_pause_count' => 'integer',
        'total_long_pause_count' => 'integer',
        'total_filler_count' => 'integer',
        'avg_filler_ratio' => 'float',
        'utterance_signals_json' => 'array',
    ];

    public function formInterview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class);
    }
}
