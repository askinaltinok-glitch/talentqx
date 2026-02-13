<?php

namespace App\Services;

use App\Models\CandidatePresentation;
use App\Models\PoolCandidate;
use App\Models\PoolCompany;
use App\Models\TalentRequest;
use Illuminate\Support\Facades\DB;

/**
 * ConsumptionService - Company Consumption Layer
 *
 * Handles the workflow of presenting candidates to companies
 * and tracking the outcome of those presentations.
 */
class ConsumptionService
{
    /**
     * Create a new pool company.
     */
    public function createCompany(array $data): PoolCompany
    {
        return PoolCompany::create([
            'company_name' => $data['company_name'],
            'industry' => $data['industry'] ?? 'general',
            'country' => $data['country'] ?? 'TR',
            'size' => $data['size'] ?? 'small',
            'contact_person' => $data['contact_person'] ?? null,
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'] ?? null,
            'status' => PoolCompany::STATUS_ACTIVE,
            'meta' => $data['meta'] ?? null,
        ]);
    }

    /**
     * Create a talent request for a company.
     * Automatically applies industry-specific defaults.
     */
    public function createTalentRequest(PoolCompany $company, array $data): TalentRequest
    {
        $industryCode = $data['industry_code'] ?? $company->industry;

        // Apply industry-specific defaults
        $defaults = $this->getIndustryDefaults($industryCode);

        return TalentRequest::create([
            'pool_company_id' => $company->id,
            'position_code' => $data['position_code'],
            'industry_code' => $industryCode,
            'required_count' => $data['required_count'] ?? 1,
            'english_required' => $data['english_required'] ?? $defaults['english_required'],
            'min_english_level' => $data['min_english_level'] ?? $defaults['min_english_level'],
            'experience_years' => $data['experience_years'] ?? null,
            'min_score' => $data['min_score'] ?? $defaults['min_score'],
            'required_competencies' => $data['required_competencies'] ?? null,
            'notes' => $data['notes'] ?? null,
            'meta' => array_merge($defaults['meta'] ?? [], $data['meta'] ?? []),
            'status' => TalentRequest::STATUS_OPEN,
        ]);
    }

    /**
     * Get industry-specific defaults for talent requests.
     */
    private function getIndustryDefaults(string $industry): array
    {
        return match ($industry) {
            'maritime' => [
                'english_required' => true,
                'min_english_level' => 'B1',
                'min_score' => 50,
                'meta' => [
                    'seafarer_only' => true,
                    'video_preferred' => true,
                ],
            ],
            'hospitality' => [
                'english_required' => true,
                'min_english_level' => 'A2',
                'min_score' => 45,
                'meta' => [],
            ],
            default => [
                'english_required' => false,
                'min_english_level' => null,
                'min_score' => null,
                'meta' => [],
            ],
        };
    }

    /**
     * Present a candidate to a company for a talent request.
     */
    public function presentCandidate(
        TalentRequest $request,
        PoolCandidate $candidate
    ): CandidatePresentation {
        return DB::transaction(function () use ($request, $candidate) {
            // Create presentation
            $presentation = CandidatePresentation::create([
                'talent_request_id' => $request->id,
                'pool_candidate_id' => $candidate->id,
                'presented_at' => now(),
                'presentation_status' => CandidatePresentation::STATUS_SENT,
            ]);

            // Update request status and count
            if ($request->status === TalentRequest::STATUS_OPEN) {
                $request->startMatching();
            }
            $request->incrementPresentedCount();

            // Update candidate status
            if ($candidate->status === PoolCandidate::STATUS_IN_POOL) {
                $candidate->markAsPresented();
            }

            return $presentation;
        });
    }

    /**
     * Present multiple candidates to a company.
     */
    public function presentCandidates(
        TalentRequest $request,
        array $candidateIds
    ): array {
        $presentations = [];

        foreach ($candidateIds as $candidateId) {
            $candidate = PoolCandidate::find($candidateId);
            if (!$candidate) {
                continue;
            }

            // Skip if already presented for this request
            $existing = CandidatePresentation::where('talent_request_id', $request->id)
                ->where('pool_candidate_id', $candidateId)
                ->exists();

            if ($existing) {
                continue;
            }

            $presentations[] = $this->presentCandidate($request, $candidate);
        }

        return $presentations;
    }

    /**
     * Record client feedback on a presentation.
     */
    public function recordFeedback(
        CandidatePresentation $presentation,
        string $feedback,
        ?int $score = null
    ): void {
        $presentation->markViewed();
        $presentation->recordFeedback($feedback, $score);
    }

    /**
     * Reject a presentation.
     */
    public function rejectPresentation(
        CandidatePresentation $presentation,
        ?string $reason = null
    ): void {
        $presentation->markRejected($reason);
    }

    /**
     * Mark presentation as interviewed.
     */
    public function markInterviewed(CandidatePresentation $presentation): void
    {
        $presentation->markInterviewed();
    }

    /**
     * Hire a candidate from presentation.
     */
    public function hireFromPresentation(
        CandidatePresentation $presentation,
        ?\DateTimeInterface $startDate = null,
        ?string $outcomeId = null
    ): void {
        $presentation->markHired($startDate, $outcomeId);
    }

    /**
     * Find matching candidates for a talent request.
     * Uses industry-specific matching criteria.
     */
    public function findMatchingCandidates(
        TalentRequest $request,
        int $limit = 20
    ): \Illuminate\Database\Eloquent\Collection {
        $query = PoolCandidate::inPool()
            ->where('primary_industry', $request->industry_code);

        // Maritime-specific: require seafarer status
        $meta = $request->meta ?? [];
        if ($request->industry_code === 'maritime' || ($meta['seafarer_only'] ?? false)) {
            $query->where('seafarer', true);
        }

        // English requirement
        if ($request->english_required && $request->min_english_level) {
            $query->minEnglishLevel($request->min_english_level);

            // For maritime, prefer candidates with completed English assessment
            if ($request->industry_code === 'maritime') {
                $query->whereHas('formInterviews', function ($q) {
                    $q->where('english_assessment_status', 'completed');
                });
            }
        }

        // Min score filter
        if ($request->min_score) {
            $query->whereHas('formInterviews', function ($q) use ($request) {
                $q->where('status', 'completed')
                    ->where(function ($q2) use ($request) {
                        $q2->where('calibrated_score', '>=', $request->min_score)
                            ->orWhere('final_score', '>=', $request->min_score);
                    });
            });
        }

        // Maritime: prefer candidates with video
        if ($meta['video_preferred'] ?? false) {
            $query->whereHas('formInterviews', function ($q) {
                $q->whereNotNull('video_assessment_url');
            });
        }

        // Exclude already presented candidates
        $presentedIds = CandidatePresentation::where('talent_request_id', $request->id)
            ->pluck('pool_candidate_id');

        if ($presentedIds->isNotEmpty()) {
            $query->whereNotIn('id', $presentedIds);
        }

        return $query->with(['formInterviews' => function ($q) {
            $q->where('status', 'completed')->latest()->limit(1);
        }])
            ->orderByDesc('last_assessed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Find best matching candidates with scoring.
     * Returns candidates ranked by match quality.
     */
    public function findBestMatches(
        TalentRequest $request,
        int $limit = 10
    ): array {
        $candidates = $this->findMatchingCandidates($request, $limit * 2);

        // Score each candidate
        $scored = $candidates->map(function ($candidate) use ($request) {
            $interview = $candidate->formInterviews->first();
            $score = 0;

            // Base score from interview
            $interviewScore = $interview?->calibrated_score ?? $interview?->final_score ?? 0;
            $score += $interviewScore * 0.4;

            // English match bonus
            if ($request->min_english_level) {
                $englishLevels = ['A1' => 1, 'A2' => 2, 'B1' => 3, 'B2' => 4, 'C1' => 5, 'C2' => 6];
                $requiredLevel = $englishLevels[$request->min_english_level] ?? 0;
                $candidateLevel = $englishLevels[$candidate->english_level_self] ?? 0;

                if ($candidateLevel >= $requiredLevel) {
                    $score += 15 + (($candidateLevel - $requiredLevel) * 5);
                }

                // Bonus for completed English assessment
                if ($interview?->english_assessment_status === 'completed') {
                    $score += 10;
                }
            }

            // Video bonus (maritime)
            if ($interview?->video_assessment_url) {
                $score += 10;
            }
            if ($interview?->video_assessment_status === 'completed') {
                $score += 5;
            }

            // Freshness bonus (recently assessed)
            if ($candidate->last_assessed_at) {
                $daysSinceAssessment = $candidate->last_assessed_at->diffInDays(now());
                if ($daysSinceAssessment < 30) {
                    $score += 10 - ($daysSinceAssessment / 3);
                }
            }

            return [
                'candidate' => $candidate,
                'interview' => $interview,
                'match_score' => round($score, 1),
            ];
        });

        // Sort by match score and take top candidates
        return $scored->sortByDesc('match_score')
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Get consumption statistics.
     */
    public function getStats(): array
    {
        $totalCompanies = PoolCompany::active()->count();
        $activeRequests = TalentRequest::active()->count();
        $totalPresentations = CandidatePresentation::count();
        $totalHired = CandidatePresentation::hired()->count();

        // Conversion rates
        $presentationToHireRate = $totalPresentations > 0
            ? round(($totalHired / $totalPresentations) * 100, 2)
            : null;

        // By status
        $presentationsByStatus = CandidatePresentation::select('presentation_status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('presentation_status')
            ->pluck('count', 'presentation_status')
            ->toArray();

        return [
            'total_companies' => $totalCompanies,
            'active_requests' => $activeRequests,
            'total_presentations' => $totalPresentations,
            'total_hired' => $totalHired,
            'presentation_to_hire_rate_pct' => $presentationToHireRate,
            'presentations_by_status' => $presentationsByStatus,
        ];
    }
}
