<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TalentRequest - Company Consumption Layer
 *
 * Represents a company's request for candidates from the talent pool.
 */
class TalentRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'pool_company_id',
        'position_code',
        'industry_code',
        'required_count',
        'english_required',
        'min_english_level',
        'experience_years',
        'min_score',
        'required_competencies',
        'notes',
        'meta',
        'status',
        'presented_count',
        'hired_count',
        'fulfilled_at',
        'closed_at',
    ];

    protected $casts = [
        'required_competencies' => 'array',
        'meta' => 'array',
        'english_required' => 'boolean',
        'fulfilled_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_OPEN = 'open';
    public const STATUS_MATCHING = 'matching';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_MATCHING,
        self::STATUS_FULFILLED,
        self::STATUS_CLOSED,
    ];

    /**
     * Get the company for this request.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(PoolCompany::class, 'pool_company_id');
    }

    /**
     * Get all presentations for this request.
     */
    public function presentations(): HasMany
    {
        return $this->hasMany(CandidatePresentation::class)
            ->orderByDesc('presented_at');
    }

    /**
     * Get hired presentations.
     */
    public function hiredPresentations(): HasMany
    {
        return $this->presentations()
            ->where('presentation_status', CandidatePresentation::STATUS_HIRED);
    }

    /**
     * Check if request is open.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Check if request is fulfilled.
     */
    public function isFulfilled(): bool
    {
        return $this->status === self::STATUS_FULFILLED;
    }

    /**
     * Start matching process.
     */
    public function startMatching(): void
    {
        $this->update(['status' => self::STATUS_MATCHING]);
    }

    /**
     * Mark as fulfilled.
     */
    public function markFulfilled(): void
    {
        $this->update([
            'status' => self::STATUS_FULFILLED,
            'fulfilled_at' => now(),
        ]);
    }

    /**
     * Close the request.
     */
    public function close(): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'closed_at' => now(),
        ]);
    }

    /**
     * Increment presented count.
     */
    public function incrementPresentedCount(int $count = 1): void
    {
        $this->increment('presented_count', $count);
    }

    /**
     * Increment hired count and check fulfillment.
     */
    public function incrementHiredCount(int $count = 1): void
    {
        $this->increment('hired_count', $count);

        // Auto-fulfill if required count is met
        if ($this->fresh()->hired_count >= $this->required_count) {
            $this->markFulfilled();
        }
    }

    /**
     * Get fill rate (hired / required).
     */
    public function getFillRateAttribute(): float
    {
        if ($this->required_count === 0) {
            return 0;
        }
        return round(($this->hired_count / $this->required_count) * 100, 1);
    }

    /**
     * Get conversion rate (hired / presented).
     */
    public function getConversionRateAttribute(): ?float
    {
        if ($this->presented_count === 0) {
            return null;
        }
        return round(($this->hired_count / $this->presented_count) * 100, 1);
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
        return $query->where('industry_code', $industry);
    }

    /**
     * Scope: open requests.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope: active requests (open or matching).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_MATCHING]);
    }
}
