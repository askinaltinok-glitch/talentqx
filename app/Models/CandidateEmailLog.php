<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateEmailLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'pool_candidate_id',
        'interview_id',
        'mail_type',
        'language',
        'to_email',
        'subject',
        'status',
        'smtp_response',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'pool_candidate_id');
    }
}
