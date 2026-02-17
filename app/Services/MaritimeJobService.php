<?php

namespace App\Services;

use App\Models\MaritimeJob;
use App\Models\MaritimeJobApplication;
use App\Models\VesselReview;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class MaritimeJobService
{
    /**
     * List active jobs with optional filters.
     */
    public function listJobs(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = MaritimeJob::active()
            ->with('company:id,company_name,industry,country');

        if (!empty($filters['rank'])) {
            $query->byRank($filters['rank']);
        }
        if (!empty($filters['vessel_type'])) {
            $query->byVesselType($filters['vessel_type']);
        }

        return $query->orderByDesc('created_at')->paginate(min($perPage, 50));
    }

    /**
     * Get job detail with company ratings.
     */
    public function getJob(string $jobId): array
    {
        $job = MaritimeJob::with('company:id,company_name,industry,country')
            ->findOrFail($jobId);

        $ratings = VesselReview::companyRatings($job->company->company_name);

        return [
            'job' => $job,
            'company_ratings' => $ratings,
            'applications_count' => $job->applications()->count(),
        ];
    }

    /**
     * Apply to a job. Checks unique constraint and monthly limit per tier.
     */
    public function apply(string $jobId, string $candidateId, string $tier = 'free'): MaritimeJobApplication
    {
        $job = MaritimeJob::active()->findOrFail($jobId);

        // Check if already applied
        $existing = MaritimeJobApplication::where('maritime_job_id', $jobId)
            ->where('pool_candidate_id', $candidateId)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'maritime_job_id' => ['You have already applied to this job.'],
            ]);
        }

        // Check monthly limit
        $maxPerMonth = config("crew_features.max_job_applications_per_month.{$tier}", 5);

        if ($maxPerMonth !== -1) {
            $thisMonthCount = MaritimeJobApplication::where('pool_candidate_id', $candidateId)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();

            if ($thisMonthCount >= $maxPerMonth) {
                throw ValidationException::withMessages([
                    'limit' => ["Monthly application limit reached ({$maxPerMonth}). Upgrade your membership for more."],
                ]);
            }
        }

        return MaritimeJobApplication::create([
            'maritime_job_id' => $jobId,
            'pool_candidate_id' => $candidateId,
            'status' => MaritimeJobApplication::STATUS_APPLIED,
        ]);
    }

    /**
     * Create a job (admin).
     */
    public function createJob(array $data): MaritimeJob
    {
        return MaritimeJob::create($data);
    }

    /**
     * Update a job (admin).
     */
    public function updateJob(string $id, array $data): MaritimeJob
    {
        $job = MaritimeJob::findOrFail($id);
        $job->update($data);
        return $job->fresh();
    }

    /**
     * Admin list with application counts.
     */
    public function getAdminList(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = MaritimeJob::with('company:id,company_name')
            ->withCount('applications');

        if (!empty($filters['is_active'])) {
            $query->where('is_active', $filters['is_active'] === 'true');
        }
        if (!empty($filters['rank'])) {
            $query->byRank($filters['rank']);
        }

        return $query->orderByDesc('created_at')->paginate(min($perPage, 50));
    }
}
