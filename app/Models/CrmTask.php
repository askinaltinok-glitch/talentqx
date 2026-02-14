<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmTask extends Model
{
    use HasUuids;

    protected $table = 'crm_tasks';

    protected $fillable = [
        'lead_id', 'type', 'title', 'description',
        'due_at', 'status', 'created_by',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    public const STATUS_OPEN = 'open';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [self::STATUS_OPEN, self::STATUS_DONE, self::STATUS_CANCELLED];

    public const TYPE_FOLLOW_UP = 'follow_up';
    public const TYPE_CALL = 'call';
    public const TYPE_MEETING_PREP = 'meeting_prep';

    public const TYPES = [self::TYPE_FOLLOW_UP, self::TYPE_CALL, self::TYPE_MEETING_PREP];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OPEN)
                     ->whereNotNull('due_at')
                     ->where('due_at', '<', now());
    }

    public function complete(): void
    {
        $this->update(['status' => self::STATUS_DONE]);
    }
}
