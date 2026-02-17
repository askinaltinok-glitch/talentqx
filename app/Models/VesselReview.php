<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VesselReview extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'vessel_reviews';

    protected $fillable = [
        'pool_candidate_id', 'company_name', 'vessel_name', 'vessel_type',
        'rating_salary', 'rating_provisions', 'rating_cabin',
        'rating_internet', 'rating_bonus', 'overall_rating',
        'comment', 'is_anonymous', 'status', 'admin_notes', 'is_demo',
        'report_count', 'published_at',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'overall_rating' => 'decimal:1',
        'rating_salary' => 'integer',
        'rating_provisions' => 'integer',
        'rating_cabin' => 'integer',
        'rating_internet' => 'integer',
        'rating_bonus' => 'integer',
        'is_demo' => 'boolean',
        'published_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED];

    public const VESSEL_TYPES = [
        'tanker', 'bulk', 'container', 'offshore', 'cruise',
        'ro_ro', 'lng', 'chemical', 'general_cargo', 'tug', 'other',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'pool_candidate_id');
    }

    /**
     * Compute overall rating from individual ratings.
     */
    public function computeOverallRating(): float
    {
        $ratings = [
            $this->rating_salary, $this->rating_provisions,
            $this->rating_cabin, $this->rating_internet, $this->rating_bonus,
        ];
        return round(array_sum($ratings) / count($ratings), 1);
    }

    public function approve(): void
    {
        $this->update(['status' => self::STATUS_APPROVED]);
    }

    public function reject(?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'admin_notes' => $notes,
        ]);
    }

    // Scopes

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForCompany($query, string $companyName)
    {
        return $query->where('company_name', $companyName);
    }

    /**
     * Get aggregate ratings for a company.
     */
    public static function companyRatings(string $companyName): array
    {
        $stats = self::approved()
            ->forCompany($companyName)
            ->selectRaw('
                COUNT(*) as review_count,
                ROUND(AVG(overall_rating), 1) as avg_overall,
                ROUND(AVG(rating_salary), 1) as avg_salary,
                ROUND(AVG(rating_provisions), 1) as avg_provisions,
                ROUND(AVG(rating_cabin), 1) as avg_cabin,
                ROUND(AVG(rating_internet), 1) as avg_internet,
                ROUND(AVG(rating_bonus), 1) as avg_bonus
            ')
            ->first();

        return $stats ? $stats->toArray() : [
            'review_count' => 0, 'avg_overall' => 0,
            'avg_salary' => 0, 'avg_provisions' => 0,
            'avg_cabin' => 0, 'avg_internet' => 0, 'avg_bonus' => 0,
        ];
    }
}
