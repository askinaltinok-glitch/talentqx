<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FleetVessel extends Model
{
    use HasUuids;

    protected $table = 'fleet_vessels';

    protected $fillable = [
        'company_id', 'imo', 'name', 'flag', 'vessel_type',
        'crew_size', 'status', 'meta',
    ];

    protected $casts = [
        'crew_size' => 'integer',
        'meta' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function manningRequirements(): HasMany
    {
        return $this->hasMany(VesselManningRequirement::class, 'vessel_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VesselAssignment::class, 'vessel_id');
    }

    public function activeAssignments(): HasMany
    {
        return $this->assignments()->whereIn('status', ['planned', 'onboard']);
    }

    /**
     * Scope: filter by tenant (company_id). Used as explicit guard.
     */
    public function scopeForTenant($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Verify this vessel belongs to the given company. Hard deny on mismatch.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function assertBelongsTo(string $companyId): void
    {
        if ($this->company_id !== $companyId) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Cross-tenant vessel access denied.'
            );
        }
    }
}
