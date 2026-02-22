<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BehavioralProfile;
use App\Models\CandidateScoringVector;
use App\Models\FormInterview;
use App\Models\LanguageAssessment;
use App\Models\ModelFeature;
use App\Models\ModelPrediction;
use App\Models\PoolCandidate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CandidatePoolController
 *
 * Admin endpoints for viewing and managing the candidate pool
 * with detailed assessment information.
 */
class CandidatePoolController extends Controller
{
    /**
     * GET /v1/admin/candidate-pool
     *
     * List candidates in pool with assessment details.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PoolCandidate::query()
            ->with([
                'formInterviews' => fn($q) => $q
                    ->where('status', 'completed')
                    ->orderByDesc('completed_at')
                    ->limit(1)
                    ->with(['modelPredictions' => fn($pq) => $pq->orderByDesc('created_at')->limit(1)]),
            ]);

        // Status filter
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'pool') {
                $query->inPool();
            } else {
                $query->where('status', $status);
            }
        }

        // Industry filter
        if ($request->filled('industry')) {
            $query->where('primary_industry', $request->input('industry'));
        }

        // Seafarer filter
        if ($request->boolean('seafarer')) {
            $query->where('seafarer', true);
        }

        // Source channel filter
        if ($request->filled('source_channel')) {
            $query->where('source_channel', $request->input('source_channel'));
        }

        // English level filter
        if ($request->filled('english_level')) {
            $query->where('english_level_self', $request->input('english_level'));
        }

        // Min score filter
        if ($request->filled('min_score')) {
            $minScore = (int) $request->input('min_score');
            $query->whereHas('formInterviews', function ($q) use ($minScore) {
                $q->where('status', 'completed')
                    ->where(function ($q2) use ($minScore) {
                        $q2->where('calibrated_score', '>=', $minScore)
                            ->orWhere('final_score', '>=', $minScore);
                    });
            });
        }

        // Assessment status filters
        if ($request->filled('english_assessment')) {
            $englishStatus = $request->input('english_assessment');
            if ($englishStatus === 'completed') {
                $query->whereHas('formInterviews', fn($q) => $q->where('english_assessment_status', 'completed'));
            } elseif ($englishStatus === 'pending') {
                $query->where('english_assessment_required', true)
                    ->whereDoesntHave('formInterviews', fn($q) => $q->where('english_assessment_status', 'completed'));
            }
        }

        if ($request->filled('video_assessment')) {
            $videoStatus = $request->input('video_assessment');
            if ($videoStatus === 'completed') {
                $query->whereHas('formInterviews', fn($q) => $q->where('video_assessment_status', 'completed'));
            } elseif ($videoStatus === 'pending') {
                $query->where('video_assessment_required', true)
                    ->whereDoesntHave('formInterviews', fn($q) => $q->where('video_assessment_status', 'completed'));
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'last_assessed_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['last_assessed_at', 'created_at', 'first_name', 'last_name', 'english_level_self'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);
        $paginated = $query->paginate($perPage);

        $data = $paginated->getCollection()->map(fn($c) => $this->formatCandidate($c));

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /v1/admin/candidate-pool/{id}
     *
     * Get detailed candidate information with full assessment history.
     */
    public function show(string $id): JsonResponse
    {
        $candidate = PoolCandidate::with([
            'formInterviews' => fn($q) => $q
                ->orderByDesc('created_at')
                ->with(['modelPredictions' => fn($pq) => $pq->orderByDesc('created_at')]),
            'presentations' => fn($q) => $q
                ->with('talentRequest.company:id,company_name')
                ->orderByDesc('presented_at'),
        ])->findOrFail($id);

        // Get model feature for latest interview
        $latestInterview = $candidate->formInterviews->first();
        $modelFeature = null;
        if ($latestInterview) {
            $modelFeature = ModelFeature::where('form_interview_id', $latestInterview->id)->first();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $candidate->id,
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'country_code' => $candidate->country_code,
                'preferred_language' => $candidate->preferred_language,
                'english_level_self' => $candidate->english_level_self,
                'source_channel' => $candidate->source_channel,
                'source_meta' => $candidate->source_meta,
                'status' => $candidate->status,
                'primary_industry' => $candidate->primary_industry,
                'seafarer' => $candidate->seafarer,
                'english_assessment_required' => $candidate->english_assessment_required,
                'video_assessment_required' => $candidate->video_assessment_required,
                'last_assessed_at' => $candidate->last_assessed_at?->toIso8601String(),
                'created_at' => $candidate->created_at->toIso8601String(),

                // Assessment summary
                'assessment' => $this->buildAssessmentSummary($candidate, $latestInterview, $modelFeature),

                // Interview history
                'interviews' => $candidate->formInterviews->map(fn($i) => [
                    'id' => $i->id,
                    'status' => $i->status,
                    'position_code' => $i->position_code,
                    'industry_code' => $i->industry_code,
                    'raw_score' => $i->raw_final_score,
                    'calibrated_score' => $i->calibrated_score,
                    'decision' => $i->decision,
                    'english_assessment_status' => $i->english_assessment_status,
                    'english_assessment_score' => $i->english_assessment_score,
                    'video_assessment_status' => $i->video_assessment_status,
                    'video_assessment_url' => $i->video_assessment_url,
                    'created_at' => $i->created_at->toIso8601String(),
                    'completed_at' => $i->completed_at?->toIso8601String(),
                    'predictions' => $i->modelPredictions->map(fn($p) => [
                        'id' => $p->id,
                        'type' => $p->prediction_type ?? 'baseline',
                        'predicted_score' => $p->predicted_outcome_score,
                        'predicted_label' => $p->predicted_label,
                        'model_version' => $p->model_version,
                        'created_at' => $p->created_at->toIso8601String(),
                    ]),
                ]),

                // Presentation history
                'presentations' => $candidate->presentations->map(fn($p) => [
                    'id' => $p->id,
                    'status' => $p->presentation_status,
                    'company' => $p->talentRequest?->company?->company_name,
                    'position' => $p->talentRequest?->position_code,
                    'presented_at' => $p->presented_at?->toIso8601String(),
                    'viewed_at' => $p->viewed_at?->toIso8601String(),
                    'client_feedback' => $p->client_feedback,
                ]),

                // ML features (if available)
                'ml_features' => $modelFeature ? [
                    'english_score' => $modelFeature->english_score,
                    'english_provider' => $modelFeature->english_provider,
                    'video_present' => $modelFeature->video_present,
                    'calibrated_score' => $modelFeature->calibrated_score,
                    'z_score' => $modelFeature->z_score,
                    'risk_flags' => $modelFeature->risk_flags_json,
                ] : null,

                // Behavioral profile (Phase B)
                'behavioral_profile' => $this->buildBehavioralProfile($candidate->id),

                // English assessment details (Phase B)
                'english_assessment' => $this->buildEnglishAssessment($candidate->id),

                // Scoring vector (Phase B)
                'scoring_vector' => $this->buildScoringVector($candidate->id),
            ],
        ]);
    }

    /**
     * GET /v1/admin/candidate-pool/stats
     *
     * Get pool statistics with assessment breakdown.
     */
    public function stats(Request $request): JsonResponse
    {
        $industry = $request->input('industry');

        $baseQuery = PoolCandidate::query()
            ->when($industry, fn($q) => $q->where('primary_industry', $industry));

        $total = (clone $baseQuery)->count();
        $inPool = (clone $baseQuery)->inPool()->count();
        $seafarers = (clone $baseQuery)->where('seafarer', true)->count();

        // Status breakdown
        $byStatus = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Source channel breakdown
        $bySource = (clone $baseQuery)
            ->inPool()
            ->selectRaw('source_channel, COUNT(*) as count')
            ->groupBy('source_channel')
            ->pluck('count', 'source_channel')
            ->toArray();

        // English level breakdown (pool only)
        $byEnglish = (clone $baseQuery)
            ->inPool()
            ->selectRaw('english_level_self, COUNT(*) as count')
            ->groupBy('english_level_self')
            ->pluck('count', 'english_level_self')
            ->toArray();

        // Assessment completion stats
        $needEnglishAssessment = (clone $baseQuery)
            ->inPool()
            ->where('english_assessment_required', true)
            ->whereDoesntHave('formInterviews', fn($q) => $q->where('english_assessment_status', 'completed'))
            ->count();

        $completedEnglishAssessment = (clone $baseQuery)
            ->inPool()
            ->whereHas('formInterviews', fn($q) => $q->where('english_assessment_status', 'completed'))
            ->count();

        $needVideoAssessment = (clone $baseQuery)
            ->inPool()
            ->where('video_assessment_required', true)
            ->whereDoesntHave('formInterviews', fn($q) => $q->where('video_assessment_status', 'completed'))
            ->count();

        $completedVideoAssessment = (clone $baseQuery)
            ->inPool()
            ->whereHas('formInterviews', fn($q) => $q->where('video_assessment_status', 'completed'))
            ->count();

        // Score distribution (pool only)
        $scoreDistribution = FormInterview::query()
            ->join('pool_candidates', 'form_interviews.pool_candidate_id', '=', 'pool_candidates.id')
            ->where('pool_candidates.status', PoolCandidate::STATUS_IN_POOL)
            ->when($industry, fn($q) => $q->where('pool_candidates.primary_industry', $industry))
            ->where('form_interviews.status', 'completed')
            ->selectRaw("
                CASE
                    WHEN COALESCE(calibrated_score, final_score) >= 80 THEN '80-100'
                    WHEN COALESCE(calibrated_score, final_score) >= 60 THEN '60-79'
                    WHEN COALESCE(calibrated_score, final_score) >= 40 THEN '40-59'
                    ELSE '0-39'
                END as score_band,
                COUNT(*) as count
            ")
            ->groupBy('score_band')
            ->pluck('count', 'score_band')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'total_candidates' => $total,
                'in_pool' => $inPool,
                'seafarers' => $seafarers,
                'by_status' => $byStatus,
                'by_source' => $bySource,
                'by_english_level' => $byEnglish,
                'score_distribution' => $scoreDistribution,
                'assessment_completion' => [
                    'english' => [
                        'completed' => $completedEnglishAssessment,
                        'pending' => $needEnglishAssessment,
                    ],
                    'video' => [
                        'completed' => $completedVideoAssessment,
                        'pending' => $needVideoAssessment,
                    ],
                ],
            ],
        ]);
    }

    /**
     * GET /v1/admin/candidate-pool/action-required
     *
     * Get candidates requiring action (pending assessments, stale, etc.)
     */
    public function actionRequired(Request $request): JsonResponse
    {
        $industry = $request->input('industry');

        // Candidates needing English assessment
        $needEnglish = PoolCandidate::inPool()
            ->where('english_assessment_required', true)
            ->whereDoesntHave('formInterviews', fn($q) => $q->where('english_assessment_status', 'completed'))
            ->when($industry, fn($q) => $q->where('primary_industry', $industry))
            ->orderByDesc('last_assessed_at')
            ->limit(10)
            ->get()
            ->map(fn($c) => $this->formatCandidateCompact($c));

        // Candidates needing video assessment
        $needVideo = PoolCandidate::inPool()
            ->where('video_assessment_required', true)
            ->whereDoesntHave('formInterviews', fn($q) => $q->where('video_assessment_status', 'completed'))
            ->when($industry, fn($q) => $q->where('primary_industry', $industry))
            ->orderByDesc('last_assessed_at')
            ->limit(10)
            ->get()
            ->map(fn($c) => $this->formatCandidateCompact($c));

        // Stale candidates (in pool > 60 days without activity)
        $stale = PoolCandidate::inPool()
            ->where('last_assessed_at', '<', now()->subDays(60))
            ->when($industry, fn($q) => $q->where('primary_industry', $industry))
            ->orderBy('last_assessed_at')
            ->limit(10)
            ->get()
            ->map(fn($c) => $this->formatCandidateCompact($c));

        // New candidates (registered in last 7 days, not yet assessed)
        $newUnassessed = PoolCandidate::where('status', PoolCandidate::STATUS_NEW)
            ->where('created_at', '>=', now()->subDays(7))
            ->when($industry, fn($q) => $q->where('primary_industry', $industry))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($c) => $this->formatCandidateCompact($c));

        return response()->json([
            'success' => true,
            'data' => [
                'need_english_assessment' => $needEnglish,
                'need_video_assessment' => $needVideo,
                'stale_candidates' => $stale,
                'new_unassessed' => $newUnassessed,
            ],
        ]);
    }

    /**
     * Format candidate for list view.
     */
    private function formatCandidate(PoolCandidate $candidate): array
    {
        $interview = $candidate->formInterviews->first();
        $prediction = $interview?->modelPredictions->first();

        return [
            'id' => $candidate->id,
            'first_name' => $candidate->first_name,
            'last_name' => $candidate->last_name,
            'email' => $candidate->email,
            'phone' => $candidate->phone,
            'country_code' => $candidate->country_code,
            'english_level_self' => $candidate->english_level_self,
            'source_channel' => $candidate->source_channel,
            'status' => $candidate->status,
            'primary_industry' => $candidate->primary_industry,
            'seafarer' => $candidate->seafarer,
            'last_assessed_at' => $candidate->last_assessed_at?->toIso8601String(),
            'created_at' => $candidate->created_at->toIso8601String(),

            // Latest interview scores
            'interview_score' => $interview?->calibrated_score ?? $interview?->raw_final_score,
            'interview_decision' => $interview?->decision,

            // Assessment status
            'english_assessment_status' => $interview?->english_assessment_status ?? 'pending',
            'english_assessment_score' => $interview?->english_assessment_score,
            'video_assessment_status' => $interview?->video_assessment_status ?? 'pending',
            'has_video' => (bool) $interview?->video_assessment_url,

            // ML prediction
            'predicted_score' => $prediction?->predicted_outcome_score,
            'predicted_label' => $prediction?->predicted_label,
        ];
    }

    /**
     * Format candidate for compact view.
     */
    private function formatCandidateCompact(PoolCandidate $candidate): array
    {
        return [
            'id' => $candidate->id,
            'full_name' => $candidate->full_name,
            'email' => $candidate->email,
            'english_level_self' => $candidate->english_level_self,
            'source_channel' => $candidate->source_channel,
            'status' => $candidate->status,
            'days_since_activity' => $candidate->last_assessed_at
                ? $candidate->last_assessed_at->diffInDays(now())
                : $candidate->created_at->diffInDays(now()),
        ];
    }

    /**
     * Build behavioral profile data for admin view.
     */
    private function buildBehavioralProfile(string $candidateId): ?array
    {
        $profile = BehavioralProfile::where('candidate_id', $candidateId)
            ->where('version', 'v1')
            ->first();

        if (!$profile) {
            return null;
        }

        return [
            'dimensions' => $profile->dimensions_json,
            'fit_json' => $profile->fit_json,
            'flags' => $profile->flags_json,
            'confidence' => $profile->confidence,
            'status' => $profile->status,
        ];
    }

    /**
     * Build English assessment data for admin view.
     */
    private function buildEnglishAssessment(string $candidateId): ?array
    {
        $assessment = LanguageAssessment::where('candidate_id', $candidateId)->first();

        if (!$assessment) {
            return null;
        }

        return [
            'overall_score' => $assessment->overall_score,
            'estimated_level' => $assessment->locked_level ?? $assessment->estimated_level,
            'confidence' => $assessment->confidence,
            'mcq_score' => $assessment->mcq_score,
            'writing_score' => $assessment->writing_score,
            'interview_score' => $assessment->interview_score,
            'declared_level' => $assessment->declared_level,
            'locked_level' => $assessment->locked_level,
            'signals' => $assessment->signals,
        ];
    }

    /**
     * Build scoring vector data for admin view.
     */
    private function buildScoringVector(string $candidateId): ?array
    {
        $vector = CandidateScoringVector::where('candidate_id', $candidateId)
            ->where('version', 'v1')
            ->first();

        if (!$vector) {
            return null;
        }

        return [
            'technical' => $vector->technical_score,
            'behavioral' => $vector->behavioral_score,
            'reliability' => $vector->reliability_score,
            'personality' => $vector->personality_score,
            'english_proficiency' => $vector->english_proficiency,
            'english_level' => $vector->english_level,
            'english_weight' => $vector->english_weight,
            'composite_score' => $vector->composite_score,
            'computed_at' => $vector->computed_at?->toIso8601String(),
        ];
    }

    /**
     * Build assessment summary for candidate detail view.
     */
    private function buildAssessmentSummary(
        PoolCandidate $candidate,
        ?FormInterview $interview,
        ?ModelFeature $feature
    ): array {
        $summary = [
            'interview' => [
                'completed' => $interview?->status === 'completed',
                'score' => $interview?->calibrated_score ?? $interview?->raw_final_score,
                'decision' => $interview?->decision,
            ],
            'english' => [
                'required' => $candidate->english_assessment_required,
                'completed' => $interview?->english_assessment_status === 'completed',
                'self_reported' => $candidate->english_level_self,
                'assessed_score' => $feature?->english_score ?? $interview?->english_assessment_score,
            ],
            'video' => [
                'required' => $candidate->video_assessment_required,
                'submitted' => (bool) $interview?->video_assessment_url,
                'completed' => $interview?->video_assessment_status === 'completed',
            ],
            'readiness' => 'not_ready',
        ];

        // Calculate readiness
        if (!$summary['interview']['completed']) {
            $summary['readiness'] = 'needs_interview';
        } elseif ($candidate->english_assessment_required && !$summary['english']['completed']) {
            $summary['readiness'] = 'needs_english';
        } elseif ($candidate->video_assessment_required && !$summary['video']['completed']) {
            $summary['readiness'] = 'needs_video';
        } else {
            $summary['readiness'] = 'ready';
        }

        return $summary;
    }
}
