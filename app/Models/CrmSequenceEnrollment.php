<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmSequenceEnrollment extends Model
{
    use HasUuids;

    protected $table = 'crm_sequence_enrollments';

    protected $fillable = [
        'lead_id', 'sequence_id', 'current_step', 'status',
        'next_step_at', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'next_step_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_ACTIVE, self::STATUS_PAUSED,
        self::STATUS_COMPLETED, self::STATUS_CANCELLED,
    ];

    // Relationships

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(CrmSequence::class, 'sequence_id');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDueNow($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('next_step_at', '<=', now());
    }

    // Methods

    public function advanceStep(): void
    {
        $steps = $this->sequence->steps ?? [];
        $nextStep = $this->current_step + 1;

        if ($nextStep >= count($steps)) {
            $this->update([
                'current_step' => $nextStep,
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now(),
                'next_step_at' => null,
            ]);
            return;
        }

        $delayDays = $steps[$nextStep]['delay_days'] ?? 3;

        $this->update([
            'current_step' => $nextStep,
            'next_step_at' => now()->addDays($delayDays),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'next_step_at' => null,
        ]);
    }

    public function pause(): void
    {
        $this->update([
            'status' => self::STATUS_PAUSED,
            'next_step_at' => null,
        ]);
    }

    public function resume(): void
    {
        $steps = $this->sequence->steps ?? [];
        $currentStepData = $steps[$this->current_step] ?? null;
        $delayDays = $currentStepData['delay_days'] ?? 1;

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'next_step_at' => now()->addDays($delayDays),
        ]);
    }
}
