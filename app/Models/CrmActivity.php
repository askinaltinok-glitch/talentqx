<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActivity extends Model
{
    use HasUuids;

    protected $table = 'crm_activities';

    protected $fillable = [
        'lead_id', 'type', 'payload', 'created_by', 'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public const TYPE_NOTE = 'note';
    public const TYPE_EMAIL_SENT = 'email_sent';
    public const TYPE_EMAIL_REPLY = 'email_reply';
    public const TYPE_CALL = 'call';
    public const TYPE_MEETING = 'meeting';
    public const TYPE_TASK = 'task';
    public const TYPE_SYSTEM = 'system';

    public const TYPES = [
        self::TYPE_NOTE, self::TYPE_EMAIL_SENT, self::TYPE_EMAIL_REPLY,
        self::TYPE_CALL, self::TYPE_MEETING, self::TYPE_TASK, self::TYPE_SYSTEM,
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }
}
