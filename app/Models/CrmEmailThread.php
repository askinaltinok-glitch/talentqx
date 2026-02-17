<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmEmailThread extends Model
{
    use HasUuids;

    protected $table = 'crm_email_threads';

    protected $fillable = [
        'lead_id', 'mailbox', 'subject',
        'last_message_at', 'message_count', 'status',
        'lang_detected', 'intent', 'industry_code', 'classification',
    ];

    protected $casts = [
        'classification' => 'array',
        'last_message_at' => 'datetime',
    ];

    public const STATUS_OPEN = 'open';
    public const STATUS_SNOOZED = 'snoozed';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_OPEN, self::STATUS_SNOOZED,
        self::STATUS_CLOSED, self::STATUS_ARCHIVED,
    ];

    public const INTENT_INQUIRY = 'inquiry';
    public const INTENT_APPLICATION = 'application';
    public const INTENT_COMPLAINT = 'complaint';
    public const INTENT_INFO_REQUEST = 'info_request';
    public const INTENT_FOLLOW_UP = 'follow_up';
    public const INTENT_SPAM = 'spam';
    public const INTENT_OTHER = 'other';

    public const INTENTS = [
        self::INTENT_INQUIRY, self::INTENT_APPLICATION, self::INTENT_COMPLAINT,
        self::INTENT_INFO_REQUEST, self::INTENT_FOLLOW_UP, self::INTENT_SPAM,
        self::INTENT_OTHER,
    ];

    // Relationships

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CrmEmailMessage::class, 'email_thread_id')->orderBy('created_at');
    }

    public function outboundQueue(): HasMany
    {
        return $this->hasMany(CrmOutboundQueue::class, 'email_thread_id');
    }

    // Scopes

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeMailbox($query, string $mailbox)
    {
        return $query->where('mailbox', $mailbox);
    }

    public function scopeIndustry($query, string $code)
    {
        return $query->where('industry_code', $code);
    }

    public function scopeSearch($query, string $q)
    {
        return $query->where('subject', 'like', "%{$q}%");
    }

    // Methods

    /**
     * Find or create a thread from an inbound/outbound message.
     * Match by In-Reply-To, References, or subject+sender normalization.
     */
    public static function findOrCreateFromMessage(array $emailData, string $mailbox): self
    {
        $inReplyTo = $emailData['in_reply_to'] ?? null;
        $references = $emailData['references'] ?? null;
        $fromEmail = $emailData['from_email'] ?? '';
        $toEmail = $emailData['to_email'] ?? '';
        $subject = $emailData['subject'] ?? '';

        // 1. Try matching via In-Reply-To → find message → get its thread
        if ($inReplyTo) {
            $replyMsg = CrmEmailMessage::where('message_id', 'like', "%{$inReplyTo}%")->first();
            if ($replyMsg && $replyMsg->email_thread_id) {
                return self::find($replyMsg->email_thread_id);
            }
        }

        // 2. Try matching via References header
        if ($references) {
            $refIds = preg_split('/[\s,]+/', $references);
            foreach ($refIds as $refId) {
                $refId = trim($refId, '<>');
                if (!$refId) continue;
                $refMsg = CrmEmailMessage::where('message_id', 'like', "%{$refId}%")->first();
                if ($refMsg && $refMsg->email_thread_id) {
                    return self::find($refMsg->email_thread_id);
                }
            }
        }

        // 3. Try matching by normalized subject + participants
        $normalizedSubject = self::normalizeSubject($subject);
        if ($normalizedSubject) {
            $thread = self::where('subject', $normalizedSubject)
                ->where('mailbox', $mailbox)
                ->where('status', '!=', self::STATUS_ARCHIVED)
                ->orderByDesc('last_message_at')
                ->first();

            if ($thread) {
                return $thread;
            }
        }

        // 4. Create new thread
        return self::create([
            'mailbox' => $mailbox,
            'subject' => $normalizedSubject ?: $subject,
            'last_message_at' => now(),
            'message_count' => 0,
            'status' => self::STATUS_OPEN,
        ]);
    }

    /**
     * Strip Re:/Fwd:/FW: prefixes from subject for thread grouping.
     */
    public static function normalizeSubject(string $subject): string
    {
        return trim(preg_replace('/^(Re|Fwd|FW|Fw|RE|İlt|Ynt)\s*:\s*/i', '', trim($subject)));
    }

    /**
     * Refresh message_count and last_message_at from related messages.
     */
    public function updateStats(): void
    {
        $this->update([
            'message_count' => $this->messages()->count(),
            'last_message_at' => $this->messages()->max('created_at') ?? $this->created_at,
        ]);
    }
}
