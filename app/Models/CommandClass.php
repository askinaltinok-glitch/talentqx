<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommandClass extends Model
{
    use HasUuids;

    protected $table = 'command_classes';

    protected $fillable = [
        'code',
        'name_en',
        'name_tr',
        'vessel_types',
        'dwt_min',
        'dwt_max',
        'trading_areas',
        'automation_levels',
        'crew_min',
        'crew_max',
        'cargo_types',
        'risk_profile',
        'certifications_required',
        'weight_vector',
        'special_considerations',
        'sub_classes',
        'is_active',
    ];

    protected $casts = [
        'vessel_types' => 'array',
        'trading_areas' => 'array',
        'automation_levels' => 'array',
        'cargo_types' => 'array',
        'risk_profile' => 'array',
        'certifications_required' => 'array',
        'weight_vector' => 'array',
        'special_considerations' => 'array',
        'sub_classes' => 'array',
        'is_active' => 'boolean',
        'dwt_min' => 'integer',
        'dwt_max' => 'integer',
        'crew_min' => 'integer',
        'crew_max' => 'integer',
    ];

    public const CODES = [
        'RIVER', 'COASTAL', 'SHORT_SEA', 'DEEP_SEA',
        'CONTAINER_ULCS', 'TANKER', 'LNG', 'OFFSHORE', 'PASSENGER',
    ];

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByVesselType($query, string $vesselType)
    {
        return $query->whereJsonContains('vessel_types', $vesselType);
    }

    public function scopeByCargoType($query, string $cargoType)
    {
        return $query->whereJsonContains('cargo_types', $cargoType);
    }

    public function scopeByDwt($query, int $dwt)
    {
        return $query->where('dwt_min', '<=', $dwt)->where('dwt_max', '>=', $dwt);
    }

    public function scopeByTradingArea($query, string $area)
    {
        return $query->whereJsonContains('trading_areas', $area);
    }

    // ── Relationships ──

    public function candidateProfiles(): HasMany
    {
        return $this->hasMany(CandidateCommandProfile::class, 'derived_command_class', 'code');
    }

    // ── Accessors ──

    public static function allActive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->get();
    }

    public function getName(string $locale = 'en'): string
    {
        return $locale === 'tr' ? $this->name_tr : $this->name_en;
    }

    public function getWeightFor(string $dimension): float
    {
        return (float) ($this->weight_vector[$dimension] ?? 0);
    }

    public function dwtInRange(int $dwt): bool
    {
        return $dwt >= $this->dwt_min && $dwt <= $this->dwt_max;
    }

    public function hasVesselType(string $type): bool
    {
        return in_array($type, $this->vessel_types, true);
    }

    public function hasTradingArea(string $area): bool
    {
        return in_array($area, $this->trading_areas, true);
    }

    public function hasCargoType(string $cargo): bool
    {
        return in_array($cargo, $this->cargo_types, true);
    }

    public function hasAutomationLevel(string $level): bool
    {
        return in_array($level, $this->automation_levels, true);
    }

    public function getRiskLevel(string $dimension): ?string
    {
        return $this->risk_profile[$dimension] ?? null;
    }
}
