<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PoolCompany - Company Consumption Layer
 *
 * Represents client companies that consume talent from the pool.
 * Separate from the existing Company model (which is for SaaS tenants).
 */
class PoolCompany extends Model
{
    use HasUuids;

    protected $table = 'pool_companies';

    protected $fillable = [
        'company_name',
        'industry',
        'country',
        'size',
        'contact_person',
        'contact_email',
        'contact_phone',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    // Size constants
    public const SIZE_SMALL = 'small';
    public const SIZE_MEDIUM = 'medium';
    public const SIZE_ENTERPRISE = 'enterprise';

    public const SIZES = [
        self::SIZE_SMALL,
        self::SIZE_MEDIUM,
        self::SIZE_ENTERPRISE,
    ];

    // Status constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * Get all talent requests for this company.
     */
    public function talentRequests(): HasMany
    {
        return $this->hasMany(TalentRequest::class, 'pool_company_id')
            ->orderByDesc('created_at');
    }

    /**
     * Get active talent requests.
     */
    public function activeTalentRequests(): HasMany
    {
        return $this->talentRequests()
            ->whereIn('status', ['open', 'matching']);
    }

    /**
     * Check if company is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get total hired count across all requests.
     */
    public function getTotalHiredAttribute(): int
    {
        return $this->talentRequests()->sum('hired_count');
    }

    /**
     * Scope: filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: filter by industry.
     */
    public function scopeIndustry($query, string $industry)
    {
        return $query->where('industry', $industry);
    }

    /**
     * Scope: active companies only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
