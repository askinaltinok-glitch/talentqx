<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmEmailMessage extends Model
{
    use HasUuids;

    protected $table = 'crm_email_messages';

    protected $fillable = [
        'lead_id', 'direction', 'provider',
        'message_id', 'thread_id', 'in_reply_to',
        'from_email', 'to_email', 'subject',
        'body_text', 'body_html', 'attachments', 'raw_headers',
        'status', 'sent_at', 'received_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'raw_headers' => 'array',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public const DIRECTION_OUTBOUND = 'outbound';
    public const DIRECTION_INBOUND = 'inbound';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_BOUNCED = 'bounced';
    public const STATUS_REPLIED = 'replied';

    public const STATUSES = [
        self::STATUS_QUEUED, self::STATUS_SENT,
        self::STATUS_DELIVERED, self::STATUS_BOUNCED, self::STATUS_REPLIED,
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', self::DIRECTION_INBOUND);
    }

    public static function findByMessageId(string $messageId): ?self
    {
        return self::where('message_id', $messageId)->first();
    }
}
