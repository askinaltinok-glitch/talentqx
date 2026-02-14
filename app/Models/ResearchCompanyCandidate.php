<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResearchCompanyCandidate extends Model
{
    use HasUuids;

    protected $table = 'research_company_candidates';

    protected $fillable = [
        'job_id', 'name', 'domain', 'country', 'city',
        'company_type', 'confidence', 'raw', 'contact_hints',
        'status', 'imported_company_id', 'reviewed_by',
    ];

    protected $casts = [
        'raw' => 'array',
        'contact_hints' => 'array',
        'confidence' => 'decimal:2',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DISMISSED = 'dismissed';

    public const STATUSES = [
        self::STATUS_PENDING, self::STATUS_ACCEPTED,
        self::STATUS_REJECTED, self::STATUS_DISMISSED,
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ResearchJob::class, 'job_id');
    }

    public function importedCompany(): BelongsTo
    {
        return $this->belongsTo(CrmCompany::class, 'imported_company_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Accept this candidate -> create CRM company + lead.
     */
    public function accept(?string $userId = null): CrmCompany
    {
        // Check for existing company by domain
        $existing = $this->domain ? CrmCompany::findByDomain($this->domain) : null;

        if ($existing) {
            $this->update([
                'status' => self::STATUS_ACCEPTED,
                'imported_company_id' => $existing->id,
                'reviewed_by' => $userId,
            ]);
            return $existing;
        }

        $company = CrmCompany::create([
            'industry_code' => $this->job?->industry_code ?? 'general',
            'name' => $this->name,
            'country_code' => $this->country ?? 'XX',
            'city' => $this->city,
            'website' => $this->domain ? "https://{$this->domain}" : null,
            'domain' => $this->domain,
            'company_type' => $this->company_type,
            'data_sources' => [['type' => 'research_agent', 'job_id' => $this->job_id, 'date' => now()->toIso8601String()]],
            'status' => CrmCompany::STATUS_NEW,
        ]);

        // Create contacts from hints
        if ($this->contact_hints) {
            foreach ($this->contact_hints as $hint) {
                $company->contacts()->create([
                    'full_name' => $hint['name'] ?? 'Unknown',
                    'email' => $hint['email'] ?? null,
                    'title' => $hint['title'] ?? null,
                ]);
            }
        }

        // Create lead
        CrmLead::create([
            'industry_code' => $company->industry_code,
            'source_channel' => CrmLead::SOURCE_RESEARCH_AGENT,
            'source_meta' => ['research_job_id' => $this->job_id],
            'company_id' => $company->id,
            'lead_name' => $company->name,
            'stage' => CrmLead::STAGE_NEW,
            'last_activity_at' => now(),
        ]);

        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'imported_company_id' => $company->id,
            'reviewed_by' => $userId,
        ]);

        return $company;
    }

    public function reject(?string $userId = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $userId,
        ]);
    }
}
