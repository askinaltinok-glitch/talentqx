<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'lead_id',
        'user_id',
        'type',
        'subject',
        'description',
        'meeting_link',
        'scheduled_at',
        'duration_minutes',
        'outcome',
        'old_status',
        'new_status',
        'is_completed',
        'due_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'due_at' => 'datetime',
        'is_completed' => 'boolean',
        'duration_minutes' => 'integer',
    ];

    // Type constants
    public const TYPE_NOTE = 'note';
    public const TYPE_CALL = 'call';
    public const TYPE_EMAIL = 'email';
    public const TYPE_MEETING = 'meeting';
    public const TYPE_DEMO = 'demo';
    public const TYPE_STATUS_CHANGE = 'status_change';
    public const TYPE_TASK = 'task';

    public const TYPE_LABELS = [
        self::TYPE_NOTE => 'Not',
        self::TYPE_CALL => 'Arama',
        self::TYPE_EMAIL => 'E-posta',
        self::TYPE_MEETING => 'Toplantı',
        self::TYPE_DEMO => 'Demo',
        self::TYPE_STATUS_CHANGE => 'Durum Değişikliği',
        self::TYPE_TASK => 'Görev',
    ];

    // Relationships
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }
}
