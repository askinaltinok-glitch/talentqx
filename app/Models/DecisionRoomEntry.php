<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionRoomEntry extends Model
{
    use HasUuids, BelongsToTenant;

    protected $table = 'decision_room_history';

    protected $fillable = [
        'fleet_vessel_id',
        'vessel_id',
        'company_id',
        'user_id',
        'rank_code',
        'action',
        'candidate_id',
        'candidate_name',
        'compatibility_snapshot',
        'risk_snapshot',
        'simulation_snapshot',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'compatibility_snapshot' => 'array',
        'risk_snapshot' => 'array',
        'simulation_snapshot' => 'array',
        'metadata' => 'array',
    ];

    public function fleetVessel(): BelongsTo
    {
        return $this->belongsTo(FleetVessel::class, 'fleet_vessel_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'candidate_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
