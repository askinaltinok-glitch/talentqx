<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselAssignment extends Model
{
    use HasUuids;

    protected $table = 'vessel_assignments';

    protected $fillable = [
        'vessel_id', 'candidate_id', 'rank_code',
        'contract_start_at', 'contract_end_at',
        'status', 'termination_reason', 'ended_early_at', 'meta',
    ];

    protected $casts = [
        'contract_start_at' => 'date',
        'contract_end_at' => 'date',
        'ended_early_at' => 'datetime',
        'meta' => 'array',
    ];

    public const STATUS_PLANNED = 'planned';
    public const STATUS_ONBOARD = 'onboard';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_TERMINATED = 'terminated_early';

    protected static function booted(): void
    {
        static::created(function (self $assignment) {
            if ($assignment->vessel) {
                event(new \App\Events\VesselCrewCompositionChanged(
                    $assignment->vessel,
                    'assignment_added',
                    $assignment->candidate_id,
                    $assignment->rank_code,
                ));
            }
        });

        static::updated(function (self $assignment) {
            if ($assignment->isDirty('status') && $assignment->vessel) {
                $changeType = in_array($assignment->status, [self::STATUS_COMPLETED, self::STATUS_TERMINATED])
                    ? 'assignment_removed'
                    : 'assignment_changed';
                event(new \App\Events\VesselCrewCompositionChanged(
                    $assignment->vessel,
                    $changeType,
                    $assignment->candidate_id,
                    $assignment->rank_code,
                ));
            }
        });

        static::deleted(function (self $assignment) {
            if ($assignment->vessel) {
                event(new \App\Events\VesselCrewCompositionChanged(
                    $assignment->vessel,
                    'assignment_removed',
                    $assignment->candidate_id,
                    $assignment->rank_code,
                ));
            }
        });
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(FleetVessel::class, 'vessel_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'candidate_id');
    }

    public function daysUntilEnd(): ?int
    {
        if (!$this->contract_end_at) return null;
        return (int) now()->startOfDay()->diffInDays($this->contract_end_at, false);
    }
}
