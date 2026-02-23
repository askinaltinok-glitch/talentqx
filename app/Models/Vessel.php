<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vessel extends Model
{
    use HasUuids;

    protected $fillable = [
        'imo', 'mmsi', 'name', 'type', 'flag', 'dwt', 'gt',
        'length_m', 'beam_m', 'year_built', 'data_source', 'last_seen_at',
        'vessel_type_raw', 'vessel_type_normalized', 'static_source',
    ];

    protected $casts = [
        'dwt' => 'integer',
        'gt' => 'integer',
        'length_m' => 'float',
        'beam_m' => 'float',
        'year_built' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    const SOURCE_MANUAL = 'manual';
    const SOURCE_AIS_API = 'ais_api';
    const SOURCE_IMPORT = 'import';

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_vessels')
            ->withPivot('role', 'is_active', 'assigned_at')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function aisVerifications(): HasMany
    {
        return $this->hasMany(AisVerification::class);
    }

    public function contractAisVerifications(): HasMany
    {
        return $this->hasMany(ContractAisVerification::class);
    }

    public function activeCrewContracts(): HasMany
    {
        return $this->hasMany(CandidateContract::class, 'vessel_imo', 'imo')->whereNull('end_date');
    }

    /**
     * Find or create a vessel stub from IMO + optional name/type/mmsi.
     */
    public static function findOrCreateByImo(string $imo, ?string $name = null, ?string $type = null, ?string $mmsi = null): self
    {
        $vessel = static::firstOrCreate(
            ['imo' => $imo],
            ['name' => $name ?? 'Unknown', 'type' => $type, 'data_source' => self::SOURCE_MANUAL]
        );

        if ($mmsi && !$vessel->mmsi) {
            $vessel->update(['mmsi' => $mmsi]);
        }

        return $vessel;
    }
}
