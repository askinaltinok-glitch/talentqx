<?php

namespace App\Services;

use App\Models\FormInterview;
use App\Models\PoolCandidate;
use App\Services\Consent\ConsentService;
use App\Services\Interview\FormInterviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class PoolCandidateService
{
    public function __construct(
        private ConsentService $consentService
    ) {}

    private function interviewService(): FormInterviewService
    {
        return App::make(FormInterviewService::class);
    }

    /**
     * Create a new pool candidate (Candidate Supply Engine).
     */
    public function create(array $data): PoolCandidate
    {
        $candidate = PoolCandidate::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'country_code' => $data['country_code'],
            'preferred_language' => $data['preferred_language'] ?? 'tr',
            'english_level_self' => $data['english_level_self'] ?? null,
            'source_channel' => $data['source_channel'],
            'source_meta' => $data['source_meta'] ?? null,
            'status' => PoolCandidate::STATUS_NEW,
            'primary_industry' => 'general',
        ]);

        return $candidate;
    }

    /**
     * Start an interview for a pool candidate.
     *
     * This creates a FormInterview linked to the candidate and records consents.
     */
    public function startInterview(
        PoolCandidate $candidate,
        string $positionCode,
        string $industryCode,
        array $consents,
        string $countryCode,
        string $regulation,
        ?Request $request = null,
        ?string $companyId = null
    ): FormInterview {
        return DB::transaction(function () use (
            $candidate,
            $positionCode,
            $industryCode,
            $consents,
            $countryCode,
            $regulation,
            $request,
            $companyId
        ) {
            // Set maritime flags if applicable
            if ($industryCode === PoolCandidate::INDUSTRY_MARITIME) {
                $candidate->setMaritimeFlags();
            }

            // Create form interview with candidate link
            $interview = $this->interviewService()->create(
                version: 'v1',
                language: $candidate->preferred_language,
                positionCode: $positionCode,
                meta: [
                    'candidate_name' => $candidate->full_name,
                    'candidate_email' => $candidate->email,
                    'english_level_self' => $candidate->english_level_self,
                ],
                industryCode: $industryCode,
                companyId: $companyId
            );

            // Link to pool candidate and snapshot acquisition data
            $interview->update([
                'pool_candidate_id' => $candidate->id,
                'acquisition_source_snapshot' => $candidate->source_channel,
                'acquisition_campaign_snapshot' => $candidate->source_meta,
            ]);

            // Record consents
            $this->consentService->recordConsents(
                $interview,
                $consents,
                $regulation,
                $request
            );

            return $interview->fresh();
        });
    }

    /**
     * Start a maritime interview with proper role/department resolution.
     */
    public function startMaritimeInterview(
        PoolCandidate $candidate,
        string $roleCode,
        string $department,
        array $consents,
        string $countryCode,
        string $regulation,
        ?Request $request = null
    ): FormInterview {
        return DB::transaction(function () use (
            $candidate, $roleCode, $department, $consents, $countryCode, $regulation, $request
        ) {
            $candidate->setMaritimeFlags();

            $interview = $this->interviewService()->create(
                version: 'v1',
                language: $candidate->preferred_language,
                positionCode: null,
                meta: [
                    'candidate_name' => $candidate->full_name,
                    'candidate_email' => $candidate->email,
                    'english_level_self' => $candidate->english_level_self,
                ],
                industryCode: PoolCandidate::INDUSTRY_MARITIME,
                roleCode: $roleCode,
                department: $department,
            );

            $interview->update([
                'pool_candidate_id' => $candidate->id,
                'acquisition_source_snapshot' => $candidate->source_channel,
                'acquisition_campaign_snapshot' => $candidate->source_meta,
            ]);

            $this->consentService->recordConsents(
                $interview,
                $consents,
                $regulation,
                $request
            );

            return $interview->fresh();
        });
    }

    /**
     * Handle auto-pooling after interview completion.
     *
     * Called by FormInterviewService::completeAndScore()
     */
    public function handleInterviewCompletion(FormInterview $interview): void
    {
        if (!$interview->pool_candidate_id) {
            return;
        }

        $candidate = $interview->poolCandidate;
        if (!$candidate) {
            return;
        }

        $decision = $interview->decision;

        // If not rejected, move to pool
        if ($decision !== 'REJECT') {
            $candidate->moveToPool($interview->industry_code ?? 'general');
        } else {
            // Rejected = assessed but not pooled
            $candidate->markAsAssessed();
        }
    }

    /**
     * Find existing candidate by email.
     */
    public function findByEmail(string $email): ?PoolCandidate
    {
        return PoolCandidate::where('email', $email)->first();
    }

    /**
     * Get candidates in pool with filters.
     */
    public function getPoolCandidates(array $filters = [], int $perPage = 25)
    {
        $query = PoolCandidate::inPool()
            ->with(['formInterviews' => fn($q) => $q->where('status', 'completed')->latest()->limit(1)]);

        // Apply filters
        if (!empty($filters['industry'])) {
            $query->industry($filters['industry']);
        }

        if (!empty($filters['source_channel'])) {
            $query->sourceChannel($filters['source_channel']);
        }

        if (!empty($filters['english_level'])) {
            $query->englishLevel($filters['english_level']);
        }

        if (!empty($filters['min_english_level'])) {
            $query->minEnglishLevel($filters['min_english_level']);
        }

        if (!empty($filters['seafarer'])) {
            $query->seafarers();
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'last_assessed_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Get pool statistics.
     */
    public function getPoolStats(): array
    {
        return [
            'total_candidates' => PoolCandidate::count(),
            'in_pool' => PoolCandidate::inPool()->count(),
            'presented' => PoolCandidate::status(PoolCandidate::STATUS_PRESENTED)->count(),
            'hired' => PoolCandidate::status(PoolCandidate::STATUS_HIRED)->count(),
            'by_industry' => PoolCandidate::inPool()
                ->selectRaw('primary_industry, count(*) as count')
                ->groupBy('primary_industry')
                ->pluck('count', 'primary_industry')
                ->toArray(),
            'by_source' => PoolCandidate::inPool()
                ->selectRaw('source_channel, count(*) as count')
                ->groupBy('source_channel')
                ->pluck('count', 'source_channel')
                ->toArray(),
        ];
    }
}
