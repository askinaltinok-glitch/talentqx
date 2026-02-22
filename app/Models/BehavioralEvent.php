<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BehavioralEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'candidate_id',
        'interview_id',
        'event_type',
        'payload_json',
        'created_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'candidate_id');
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class, 'interview_id');
    }
}
