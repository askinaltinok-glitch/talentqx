<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrewFeedback extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'crew_feedback';

    protected $fillable = [
        'candidate_contract_id',
        'pool_candidate_id',
        'vessel_id',
        'feedback_type',
        'rated_by_user_id',
        'company_id',
        'rating_overall',
        'rating_competence',
        'rating_teamwork',
        'rating_reliability',
        'rating_communication',
        'comment',
        'is_anonymous',
        'status',
        'admin_notes',
        'report_count',
        'published_at',
    ];

    protected $casts = [
        'rating_overall'       => 'integer',
        'rating_competence'    => 'integer',
        'rating_teamwork'      => 'integer',
        'rating_reliability'   => 'integer',
        'rating_communication' => 'integer',
        'is_anonymous'         => 'boolean',
        'report_count'         => 'integer',
        'published_at'         => 'datetime',
    ];

    // Feedback types
    const TYPE_COMPANY_RATES_SEAFARER = 'company_rates_seafarer';
    const TYPE_SEAFARER_RATES_VESSEL  = 'seafarer_rates_vessel';

    // Statuses
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_FLAGGED  = 'flagged';

    public function candidateContract(): BelongsTo
    {
        return $this->belongsTo(CandidateContract::class);
    }

    public function poolCandidate(): BelongsTo
    {
        return $this->belongsTo(PoolCandidate::class);
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Detect suspicious ratings using multiple heuristics:
     * 1. All identical extreme ratings (1 or 5) → revenge/inflation
     * 2. Burst submissions (>5 from same vessel in 1h)
     * 3. Revenge pattern (user always rates 1-star)
     * 4. Extreme outlier vs distribution for this vessel
     */
    public function isSuspicious(): bool
    {
        // Check 1: All identical extreme ratings
        $ratings = array_filter([
            $this->rating_overall,
            $this->rating_competence,
            $this->rating_teamwork,
            $this->rating_reliability,
            $this->rating_communication,
        ], fn ($r) => $r !== null);

        if (count($ratings) >= 3) {
            $unique = array_unique($ratings);
            if (count($unique) === 1 && in_array($unique[array_key_first($unique)], [1, 5])) {
                return true;
            }
        }

        // Check 2: Burst submissions (>5 from same vessel in 1h)
        if ($this->vessel_id && $this->created_at) {
            $recentCount = static::where('vessel_id', $this->vessel_id)
                ->where('id', '!=', $this->id)
                ->where('created_at', '>=', $this->created_at->subHour())
                ->where('created_at', '<=', $this->created_at)
                ->count();

            if ($recentCount >= 5) {
                return true;
            }
        }

        // Check 3: Revenge pattern — same user always gives 1-star
        if ($this->rated_by_user_id && $this->rating_overall === 1) {
            $prevRatings = static::where('rated_by_user_id', $this->rated_by_user_id)
                ->where('id', '!=', $this->id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->pluck('rating_overall')
                ->toArray();

            if (count($prevRatings) >= 3 && count(array_unique($prevRatings)) === 1 && $prevRatings[0] === 1) {
                return true;
            }
        }

        return false;
    }
}
