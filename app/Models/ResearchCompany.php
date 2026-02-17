<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResearchCompany extends Model
{
    use HasUuids;

    protected $table = 'research_companies';

    protected $fillable = [
        'name', 'domain', 'country', 'industry', 'sub_industry',
        'maritime_flag', 'hiring_signal_score', 'source', 'source_meta',
        'website', 'linkedin_url', 'description',
        'employee_count_est', 'fleet_size_est', 'fleet_type',
        'vessel_count', 'crew_size_est', 'target_list',
        'discovered_at', 'enriched_at', 'status', 'classification',
    ];

    protected $casts = [
        'source_meta' => 'array',
        'classification' => 'array',
        'maritime_flag' => 'boolean',
        'target_list' => 'boolean',
        'discovered_at' => 'datetime',
        'enriched_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_DISCOVERED = 'discovered';
    public const STATUS_ENRICHED = 'enriched';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_PUSHED = 'pushed';
    public const STATUS_IGNORED = 'ignored';

    public const STATUSES = [
        self::STATUS_DISCOVERED, self::STATUS_ENRICHED,
        self::STATUS_QUALIFIED, self::STATUS_PUSHED, self::STATUS_IGNORED,
    ];

    // Source constants
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_IMPORT = 'import';
    public const SOURCE_EMAIL_DOMAIN = 'email_domain';
    public const SOURCE_WEBHOOK = 'webhook';

    public const SOURCES = [
        self::SOURCE_MANUAL, self::SOURCE_IMPORT,
        self::SOURCE_EMAIL_DOMAIN, self::SOURCE_WEBHOOK,
    ];

    // Relationships

    public function signals(): HasMany
    {
        return $this->hasMany(ResearchSignal::class, 'research_company_id');
    }

    // Scopes

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeMaritime($query)
    {
        return $query->where('maritime_flag', true);
    }

    public function scopeMinScore($query, int $score)
    {
        return $query->where('hiring_signal_score', '>=', $score);
    }

    public function scopeSearch($query, string $q)
    {
        return $query->where(function ($qb) use ($q) {
            $qb->where('name', 'like', "%{$q}%")
               ->orWhere('domain', 'like', "%{$q}%")
               ->orWhere('description', 'like', "%{$q}%");
        });
    }

    public function scopeIndustry($query, string $industry)
    {
        return $query->where('industry', $industry);
    }

    // Helpers

    public static function findByDomain(string $domain): ?self
    {
        return self::where('domain', $domain)->first();
    }

    public static function findOrCreateByDomain(string $domain, array $defaults = []): self
    {
        $existing = self::findByDomain($domain);
        if ($existing) {
            return $existing;
        }

        return self::create(array_merge([
            'name' => $domain,
            'domain' => $domain,
            'status' => self::STATUS_DISCOVERED,
            'discovered_at' => now(),
        ], $defaults));
    }

    public function recalculateHiringScore(): void
    {
        $avg = $this->signals()->avg('confidence_score');
        $this->update(['hiring_signal_score' => (int) round($avg ?? 0)]);
    }

    /**
     * Push this research company into the CRM as a CrmCompany + CrmLead.
     * Mirrors ResearchCompanyCandidate::accept() pattern.
     */
    public function pushToCrm(?string $userId = null): ?CrmCompany
    {
        // Dedup via domain
        if ($this->domain) {
            $existing = CrmCompany::findByDomain($this->domain);
            if ($existing) {
                $this->update(['status' => self::STATUS_PUSHED]);
                return $existing;
            }
        }

        $companyType = $this->classification['company_type'] ?? null;

        $company = CrmCompany::create([
            'industry_code' => $this->industry ?? 'general',
            'name' => $this->name,
            'country_code' => $this->country ?? 'XX',
            'website' => $this->website ?? ($this->domain ? "https://{$this->domain}" : null),
            'domain' => $this->domain,
            'linkedin_url' => $this->linkedin_url,
            'company_type' => $companyType,
            'data_sources' => [['type' => 'research_intelligence', 'research_company_id' => $this->id, 'date' => now()->toIso8601String()]],
            'status' => CrmCompany::STATUS_NEW,
        ]);

        CrmLead::create([
            'industry_code' => $this->industry,
            'source_channel' => CrmLead::SOURCE_RESEARCH_AGENT,
            'source_meta' => [
                'research_company_id' => $this->id,
                'hiring_signal_score' => $this->hiring_signal_score,
                'maritime_flag' => $this->maritime_flag,
            ],
            'company_id' => $company->id,
            'lead_name' => $this->name,
            'stage' => CrmLead::STAGE_NEW,
            'last_activity_at' => now(),
        ]);

        $this->update(['status' => self::STATUS_PUSHED]);

        return $company;
    }
}
