<?php

namespace App\Models;

use App\Models\Traits\IsDemoScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmDeal extends Model
{
    use HasUuids, IsDemoScoped;

    protected $table = 'crm_deals';

    protected $fillable = [
        'lead_id', 'company_id', 'contact_id', 'industry_code',
        'deal_name', 'stage', 'value', 'currency', 'probability',
        'expected_close_at', 'won_at', 'lost_at', 'lost_reason',
        'owner_user_id', 'notes', 'is_demo',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_at' => 'date',
        'won_at' => 'datetime',
        'lost_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    // Maritime pipeline: prospect → contacted → demo_scheduled → pilot → paying
    public const STAGES_MARITIME = [
        'prospect', 'contacted', 'demo_scheduled', 'pilot', 'paying',
    ];

    // HR/General pipeline: lead → discovery → demo → proposal → closed_won
    public const STAGES_HR = [
        'lead', 'discovery', 'demo', 'proposal', 'closed_won',
    ];

    // Default probability per stage
    public const STAGE_PROBABILITIES = [
        // Maritime
        'prospect' => 10, 'contacted' => 20, 'demo_scheduled' => 40, 'pilot' => 70, 'paying' => 100,
        // HR
        'lead' => 10, 'discovery' => 20, 'demo' => 40, 'proposal' => 70, 'closed_won' => 100,
    ];

    public const WON_STAGES = ['paying', 'closed_won'];
    public const LOST_STAGE = 'lost';

    /**
     * Get the pipeline stages for a given industry.
     */
    public static function stagesFor(string $industry): array
    {
        return match ($industry) {
            'maritime' => self::STAGES_MARITIME,
            default => self::STAGES_HR,
        };
    }

    /**
     * Get the initial stage for a given industry.
     */
    public static function initialStage(string $industry): string
    {
        return self::stagesFor($industry)[0];
    }

    // Relationships

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(CrmCompany::class, 'company_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }

    public function stageHistory(): HasMany
    {
        return $this->hasMany(CrmDealStageHistory::class, 'deal_id')->orderBy('created_at');
    }

    // Scopes

    public function scopeIndustry($query, string $code)
    {
        return $query->where('industry_code', $code);
    }

    public function scopeStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('won_at')->whereNull('lost_at');
    }

    public function scopeWon($query)
    {
        return $query->whereNotNull('won_at');
    }

    public function scopeLost($query)
    {
        return $query->whereNotNull('lost_at');
    }

    public function scopePipeline($query, string $industry)
    {
        return $query->where('industry_code', $industry)->open();
    }

    public function scopeSearch($query, string $q)
    {
        return $query->where('deal_name', 'like', "%{$q}%");
    }

    // Methods

    public function isOpen(): bool
    {
        return !$this->won_at && !$this->lost_at;
    }

    public function isWon(): bool
    {
        return (bool) $this->won_at;
    }

    public function isLost(): bool
    {
        return (bool) $this->lost_at;
    }

    /**
     * Advance deal to the next stage in the pipeline.
     */
    public function advanceStage(?string $changedBy = null): self
    {
        $stages = self::stagesFor($this->industry_code);
        $currentIndex = array_search($this->stage, $stages);

        if ($currentIndex === false || $currentIndex >= count($stages) - 1) {
            return $this; // Already at last stage or unknown stage
        }

        $nextStage = $stages[$currentIndex + 1];
        return $this->moveToStage($nextStage, $changedBy);
    }

    /**
     * Move deal to a specific stage.
     */
    public function moveToStage(string $newStage, ?string $changedBy = null): self
    {
        $fromStage = $this->stage;

        $this->update([
            'stage' => $newStage,
            'probability' => self::STAGE_PROBABILITIES[$newStage] ?? $this->probability,
        ]);

        CrmDealStageHistory::create([
            'deal_id' => $this->id,
            'from_stage' => $fromStage,
            'to_stage' => $newStage,
            'changed_by' => $changedBy,
            'created_at' => now(),
        ]);

        // Auto-win if moved to a won stage
        if (in_array($newStage, self::WON_STAGES) && !$this->won_at) {
            $this->update(['won_at' => now()]);
        }

        return $this;
    }

    /**
     * Mark deal as won.
     */
    public function win(?string $changedBy = null): self
    {
        $wonStage = $this->industry_code === 'maritime' ? 'paying' : 'closed_won';
        $this->moveToStage($wonStage, $changedBy);
        $this->update([
            'won_at' => now(),
            'probability' => 100,
        ]);

        // Update lead stage
        if ($this->lead) {
            $this->lead->update(['stage' => CrmLead::STAGE_WON]);
        }

        return $this;
    }

    /**
     * Mark deal as lost.
     */
    public function lose(?string $reason = null, ?string $changedBy = null): self
    {
        $fromStage = $this->stage;

        $this->update([
            'stage' => self::LOST_STAGE,
            'lost_at' => now(),
            'lost_reason' => $reason,
            'probability' => 0,
        ]);

        CrmDealStageHistory::create([
            'deal_id' => $this->id,
            'from_stage' => $fromStage,
            'to_stage' => self::LOST_STAGE,
            'changed_by' => $changedBy,
            'created_at' => now(),
        ]);

        return $this;
    }

    /**
     * Get average deal age in days for a pipeline query.
     */
    public static function avgDealAgeDays(string $industry): float
    {
        return static::pipeline($industry)
            ->selectRaw('AVG(DATEDIFF(NOW(), created_at)) as avg_days')
            ->value('avg_days') ?? 0;
    }
}
