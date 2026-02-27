<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionAuditLog extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    const ACTION_APPROVE = 'approve';
    const ACTION_REJECT = 'reject';
    const ACTION_OVERRIDE = 'override';
    const ACTION_DOWNLOAD_PACKET = 'download_packet';

    protected $fillable = [
        'interview_id',
        'candidate_id',
        'action',
        'performed_by',
        'old_state',
        'new_state',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function interview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class, 'interview_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'candidate_id');
    }

    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
