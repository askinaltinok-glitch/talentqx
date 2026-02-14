<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmCompany extends Model
{
    use HasUuids;

    protected $table = 'crm_companies';

    protected $fillable = [
        'industry_code', 'name', 'country_code', 'city',
        'website', 'domain', 'linkedin_url',
        'company_type', 'size_band', 'tags', 'data_sources',
        'status', 'owner_user_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'data_sources' => 'array',
    ];

    public const STATUS_NEW = 'new';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_ACTIVE_CLIENT = 'active_client';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_NEW, self::STATUS_QUALIFIED, self::STATUS_CONTACTED,
        self::STATUS_ACTIVE_CLIENT, self::STATUS_ARCHIVED,
    ];

    public const COMPANY_TYPES = [
        'ship_manager', 'ship_owner', 'agency', 'charterer',
        'manning_agent', 'training_center', 'offshore_operator',
        'tanker_operator', 'medical_clinic', 'classification_society',
        'retail', 'factory', 'logistics', 'other',
    ];

    public const SIZE_BANDS = ['1-10', '11-50', '51-200', '200+'];

    public const INDUSTRY_GENERAL = 'general';
    public const INDUSTRY_MARITIME = 'maritime';

    // Relationships

    public function contacts(): HasMany
    {
        return $this->hasMany(CrmContact::class, 'company_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CrmLead::class, 'company_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(CrmFile::class, 'company_id');
    }

    // Scopes

    public function scopeIndustry($query, string $code)
    {
        return $query->where('industry_code', $code);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCountry($query, string $cc)
    {
        return $query->where('country_code', $cc);
    }

    public function scopeSearch($query, string $q)
    {
        return $query->where(function ($qb) use ($q) {
            $qb->where('name', 'like', "%{$q}%")
               ->orWhere('domain', 'like', "%{$q}%")
               ->orWhere('city', 'like', "%{$q}%");
        });
    }

    // Helpers

    public static function extractDomain(?string $website): ?string
    {
        if (!$website) return null;
        $host = parse_url($website, PHP_URL_HOST);
        if (!$host) return null;
        return preg_replace('/^www\./', '', strtolower($host));
    }

    public static function findByDomain(string $domain): ?self
    {
        return self::where('domain', $domain)->first();
    }
}
