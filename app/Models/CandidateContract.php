<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CandidateContract extends Model
{
    use HasUuids;

    protected $fillable = [
        'pool_candidate_id',
        'vessel_name',
        'vessel_imo',
        'vessel_type',
        'vessel_id',
        'company_name',
        'rank_code',
        'start_date',
        'end_date',
        'trading_area',
        'dwt_range',
        'source',
        'verified',
        'verified_by_company_id',
        'verified_by_user_id',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    // Sources
    const SOURCE_SELF_DECLARED = 'self_declared';
    const SOURCE_AIS = 'ais';
    const SOURCE_COMPANY_VERIFIED = 'company_verified';

    // Vessel types
    const VESSEL_BULK_CARRIER = 'bulk_carrier';
    const VESSEL_TANKER = 'tanker';
    const VESSEL_CONTAINER = 'container';
    const VESSEL_GENERAL_CARGO = 'general_cargo';
    const VESSEL_RORO = 'ro_ro';
    const VESSEL_PASSENGER = 'passenger';
    const VESSEL_OFFSHORE = 'offshore';
    const VESSEL_LNG_LPG = 'lng_lpg';
    const VESSEL_CHEMICAL = 'chemical';
    const VESSEL_CAR_CARRIER = 'car_carrier';
    const VESSEL_RIVER = 'river_vessel';
    const VESSEL_OTHER = 'other';

    public function poolCandidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class);
    }

    public function aisVerification(): HasOne
    {
        return $this->hasOne(AisVerification::class, 'candidate_contract_id');
    }

    public function contractAisVerifications(): HasMany
    {
        return $this->hasMany(ContractAisVerification::class, 'candidate_contract_id');
    }

    public function latestAisVerification(): HasOne
    {
        return $this->hasOne(ContractAisVerification::class, 'candidate_contract_id')->latestOfMany('created_at');
    }

    public function seaTimeLog(): HasOne
    {
        return $this->hasOne(SeaTimeLog::class, 'candidate_contract_id');
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class, 'vessel_imo', 'imo');
    }

    public function durationMonths(): float
    {
        $end = $this->end_date ?? now();
        return round($this->start_date->diffInDays($end) / 30.44, 1);
    }

    public function isOngoing(): bool
    {
        return $this->end_date === null;
    }

    /**
     * Mark this contract as company-verified.
     */
    public function markVerified(string $companyId, string $userId): void
    {
        $this->update([
            'source' => self::SOURCE_COMPANY_VERIFIED,
            'verified' => true,
            'verified_by_company_id' => $companyId,
            'verified_by_user_id' => $userId,
            'verified_at' => now(),
        ]);
    }

    public function scopeForCandidate($query, string $candidateId)
    {
        return $query->where('pool_candidate_id', $candidateId);
    }

    public function scopeChronological($query)
    {
        return $query->orderBy('start_date');
    }
}
