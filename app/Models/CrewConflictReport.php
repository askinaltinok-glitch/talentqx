<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrewConflictReport extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'vessel_id',
        'reporter_candidate_id',
        'target_candidate_id',
        'category',
        'rating',
        'comment',
        'is_anonymous',
        'is_suspicious',
        'suspicion_reason',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_anonymous' => 'boolean',
        'is_suspicious' => 'boolean',
    ];

    // Categories
    const CAT_LEADERSHIP_CONFLICT = 'leadership_conflict';
    const CAT_COMMUNICATION_BREAKDOWN = 'communication_breakdown';
    const CAT_HARASSMENT = 'harassment';
    const CAT_WORKLOAD_FAIRNESS = 'workload_fairness';
    const CAT_ACCOMMODATION = 'accommodation';
    const CAT_SALARY_DELAY = 'salary_delay';
    const CAT_FOOD_QUALITY = 'food_quality';

    const VALID_CATEGORIES = [
        self::CAT_LEADERSHIP_CONFLICT,
        self::CAT_COMMUNICATION_BREAKDOWN,
        self::CAT_HARASSMENT,
        self::CAT_WORKLOAD_FAIRNESS,
        self::CAT_ACCOMMODATION,
        self::CAT_SALARY_DELAY,
        self::CAT_FOOD_QUALITY,
    ];

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'reporter_candidate_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class, 'target_candidate_id');
    }

    /**
     * Detect suspicious conflict report patterns.
     * Extended from CrewFeedback.isSuspicious() with additional checks.
     */
    public static function detectSuspicion(array $data, ?string $companyId = null, ?string $vesselId = null): array
    {
        $isSuspicious = false;
        $reason = null;

        // Check burst submissions (>5 reports from same vessel in last hour)
        if ($companyId && $vesselId) {
            $recentCount = static::withoutTenantScope()
                ->where('company_id', $companyId)
                ->where('vessel_id', $vesselId)
                ->where('created_at', '>=', now()->subHour())
                ->count();

            if ($recentCount >= 5) {
                $isSuspicious = true;
                $reason = 'burst_submission: ' . ($recentCount + 1) . ' reports from same vessel in 1h';
            }
        }

        // Check extreme outlier: always rating 1
        if (!$isSuspicious && ($data['rating'] ?? 3) === 1) {
            // Check if reporter always gives 1-star
            $reporterId = $data['reporter_candidate_id'] ?? null;
            if ($reporterId) {
                $prevReports = static::withoutTenantScope()
                    ->where('reporter_candidate_id', $reporterId)
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->pluck('rating')
                    ->toArray();

                if (count($prevReports) >= 3 && count(array_unique($prevReports)) === 1 && $prevReports[0] === 1) {
                    $isSuspicious = true;
                    $reason = 'revenge_pattern: reporter consistently gives 1-star ratings';
                }
            }
        }

        return ['is_suspicious' => $isSuspicious, 'suspicion_reason' => $reason];
    }
}
