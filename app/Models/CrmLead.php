<?php

namespace App\Models;

use App\Models\Traits\IsDemoScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmLead extends Model
{
    use HasUuids, IsDemoScoped;

    protected $table = 'crm_leads';

    protected $fillable = [
        'industry_code', 'source_channel', 'source_meta',
        'company_id', 'contact_id', 'lead_name',
        'stage', 'priority', 'notes', 'is_demo', 'preferred_language',
        'last_activity_at', 'last_contacted_at', 'next_follow_up_at',
    ];

    protected $casts = [
        'source_meta' => 'array',
        'last_activity_at' => 'datetime',
        'last_contacted_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    public const STAGE_NEW = 'new';
    public const STAGE_CONTACTED = 'contacted';
    public const STAGE_MEETING = 'meeting';
    public const STAGE_PROPOSAL = 'proposal';
    public const STAGE_NEGOTIATION = 'negotiation';
    public const STAGE_WON = 'won';
    public const STAGE_LOST = 'lost';

    public const STAGES = [
        self::STAGE_NEW, self::STAGE_CONTACTED, self::STAGE_MEETING,
        self::STAGE_PROPOSAL, self::STAGE_NEGOTIATION,
        self::STAGE_WON, self::STAGE_LOST,
    ];

    public const SOURCE_WEBSITE_FORM = 'website_form';
    public const SOURCE_INBOUND_EMAIL = 'inbound_email';
    public const SOURCE_REFERRAL = 'referral';
    public const SOURCE_RESEARCH_AGENT = 'research_agent';
    public const SOURCE_DEMO = 'demo';
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_CHANNELS = [
        self::SOURCE_WEBSITE_FORM, self::SOURCE_INBOUND_EMAIL,
        self::SOURCE_REFERRAL, self::SOURCE_RESEARCH_AGENT,
        self::SOURCE_DEMO, self::SOURCE_MANUAL,
    ];

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MED = 'med';
    public const PRIORITY_HIGH = 'high';

    public const PRIORITIES = [self::PRIORITY_LOW, self::PRIORITY_MED, self::PRIORITY_HIGH];

    // Relationships

    public function company(): BelongsTo
    {
        return $this->belongsTo(CrmCompany::class, 'company_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class, 'lead_id')->orderByDesc('occurred_at');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(CrmEmailMessage::class, 'lead_id')->orderByDesc('created_at');
    }

    public function files(): HasMany
    {
        return $this->hasMany(CrmFile::class, 'lead_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CrmTask::class, 'lead_id');
    }

    public function threads(): HasMany
    {
        return $this->hasMany(CrmEmailThread::class, 'lead_id');
    }

    public function sequenceEnrollments(): HasMany
    {
        return $this->hasMany(CrmSequenceEnrollment::class, 'lead_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(CrmDeal::class, 'lead_id')->orderByDesc('created_at');
    }

    // Scopes

    public function scopeStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopeIndustry($query, string $code)
    {
        return $query->where('industry_code', $code);
    }

    public function scopeSearch($query, string $q)
    {
        return $query->where('lead_name', 'like', "%{$q}%");
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('stage', [self::STAGE_WON, self::STAGE_LOST]);
    }

    // Helpers

    public function touchActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function addActivity(string $type, array $payload = [], ?string $createdBy = null): CrmActivity
    {
        $activity = $this->activities()->create([
            'type' => $type,
            'payload' => $payload,
            'created_by' => $createdBy,
            'occurred_at' => now(),
        ]);

        $this->touchActivity();

        return $activity;
    }
}
