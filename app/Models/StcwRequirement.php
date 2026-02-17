<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * StcwRequirement — STCW certification requirements per rank/vessel type.
 *
 * Maps which certificates are required for each rank.
 * Used by CertificationService to check compliance.
 */
class StcwRequirement extends Model
{
    protected $fillable = [
        'rank_code',
        'department',
        'vessel_type',
        'required_certificates',
        'mandatory',
        'notes',
    ];

    protected $casts = [
        'required_certificates' => 'array',
        'mandatory' => 'boolean',
    ];

    // Departments
    public const DEPT_DECK = 'deck';
    public const DEPT_ENGINE = 'engine';
    public const DEPT_HOTEL = 'hotel';
    public const DEPT_OTHER = 'other';

    // Vessel types
    public const VESSEL_ANY = 'any';
    public const VESSEL_TANKER = 'tanker';
    public const VESSEL_PASSENGER = 'passenger';
    public const VESSEL_CARGO = 'cargo';
    public const VESSEL_OFFSHORE = 'offshore';

    /**
     * Scope: for a specific rank.
     */
    public function scopeForRank($query, string $rankCode)
    {
        return $query->where('rank_code', $rankCode);
    }

    /**
     * Scope: for a specific vessel type (includes 'any').
     */
    public function scopeForVessel($query, string $vesselType = 'any')
    {
        return $query->where(function ($q) use ($vesselType) {
            $q->where('vessel_type', $vesselType)
              ->orWhere('vessel_type', 'any');
        });
    }

    /**
     * Get all required certificate codes for a rank + vessel type.
     */
    public static function getRequiredCodes(string $rankCode, string $vesselType = 'any'): array
    {
        $requirements = static::forRank($rankCode)
            ->forVessel($vesselType)
            ->where('mandatory', true)
            ->get();

        $codes = [];
        foreach ($requirements as $req) {
            $codes = array_merge($codes, $req->required_certificates);
        }

        return array_unique($codes);
    }
}
