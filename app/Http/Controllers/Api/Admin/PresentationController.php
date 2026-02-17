<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidatePresentation;
use App\Models\CandidateProfileView;
use App\Services\CandidateNotificationService;
use App\Services\ConsumptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresentationController extends Controller
{
    public function __construct(
        private ConsumptionService $service,
        private CandidateNotificationService $notificationService,
    ) {}

    /**
     * GET /v1/admin/presentations
     * List all presentations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CandidatePresentation::query()
            ->with([
                'talentRequest:id,pool_company_id,position_code,industry_code',
                'talentRequest.company:id,company_name',
                'poolCandidate:id,first_name,last_name,email,primary_industry,english_level_self,source_channel',
            ]);

        // Filters
        if ($request->filled('status')) {
            $query->status($request->input('status'));
        }
        if ($request->filled('talent_request_id')) {
            $query->where('talent_request_id', $request->input('talent_request_id'));
        }
        if ($request->filled('industry')) {
            $query->whereHas('talentRequest', function ($q) use ($request) {
                $q->where('industry_code', $request->input('industry'));
            });
        }
        if ($request->filled('source_channel')) {
            $query->whereHas('poolCandidate', function ($q) use ($request) {
                $q->where('source_channel', $request->input('source_channel'));
            });
        }
        if ($request->filled('from')) {
            $query->where('presented_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->where('presented_at', '<=', $request->input('to'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $paginated = $query->orderByDesc('presented_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /v1/admin/presentations/{id}
     * Get presentation details.
     */
    public function show(CandidatePresentation $presentation): JsonResponse
    {
        $presentation->load([
            'talentRequest.company',
            'poolCandidate.formInterviews' => function ($q) {
                $q->where('status', 'completed')->latest()->limit(1);
            },
            'interviewOutcome',
        ]);

        $interview = $presentation->poolCandidate->formInterviews->first();

        return response()->json([
            'success' => true,
            'data' => [
                ...$presentation->toArray(),
                'candidate_score' => $interview?->calibrated_score ?? $interview?->final_score,
                'candidate_decision' => $interview?->decision,
                'candidate_competency_scores' => $interview?->competency_scores,
                'candidate_risk_flags' => $interview?->risk_flags,
            ],
        ]);
    }

    /**
     * POST /v1/admin/presentations/{id}/view
     * Mark presentation as viewed.
     */
    public function markViewed(CandidatePresentation $presentation): JsonResponse
    {
        $presentation->markViewed();

        // Record profile view + notification for the candidate
        if ($presentation->pool_candidate_id) {
            $candidate = $presentation->poolCandidate;
            $companyName = $presentation->talentRequest?->company?->company_name;

            if ($candidate) {
                $this->notificationService->notifyProfileView(
                    $candidate,
                    CandidateProfileView::VIEWER_COMPANY,
                    $presentation->talentRequest?->pool_company_id,
                    $companyName,
                    CandidateProfileView::CONTEXT_PRESENTATION,
                    [
                        'talent_request_id' => $presentation->talent_request_id,
                        'position_code' => $presentation->talentRequest?->position_code,
                    ],
                    $companyName
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Presentation marked as viewed',
            'data' => $presentation->fresh(),
        ]);
    }

    /**
     * POST /v1/admin/presentations/{id}/feedback
     * Record client feedback.
     */
    public function recordFeedback(Request $request, CandidatePresentation $presentation): JsonResponse
    {
        $data = $request->validate([
            'feedback' => ['required', 'string', 'max:2000'],
            'score' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $this->service->recordFeedback(
            $presentation,
            $data['feedback'],
            $data['score'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Feedback recorded successfully',
            'data' => $presentation->fresh(),
        ]);
    }

    /**
     * POST /v1/admin/presentations/{id}/reject
     * Reject a presentation.
     */
    public function reject(Request $request, CandidatePresentation $presentation): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($presentation->isHired()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reject a hired candidate',
            ], 422);
        }

        $this->service->rejectPresentation($presentation, $data['reason'] ?? null);

        // Notify candidate of rejection
        if ($presentation->poolCandidate) {
            $companyName = $presentation->talentRequest?->company?->company_name;
            $this->notificationService->notifyStatusChange(
                $presentation->poolCandidate,
                'rejected',
                [
                    'company_name' => $companyName,
                    'presentation_id' => $presentation->id,
                    'reason' => $data['reason'] ?? null,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Presentation rejected',
            'data' => $presentation->fresh(),
        ]);
    }

    /**
     * POST /v1/admin/presentations/{id}/interview
     * Schedule or mark as interviewed.
     */
    public function interview(Request $request, CandidatePresentation $presentation): JsonResponse
    {
        $data = $request->validate([
            'scheduled_at' => ['nullable', 'date'],
            'mark_completed' => ['nullable', 'boolean'],
        ]);

        if ($data['scheduled_at'] ?? false) {
            $presentation->scheduleInterview(new \DateTime($data['scheduled_at']));
        }

        if ($data['mark_completed'] ?? false) {
            $this->service->markInterviewed($presentation);
        }

        return response()->json([
            'success' => true,
            'message' => 'Interview updated',
            'data' => $presentation->fresh(),
        ]);
    }

    /**
     * POST /v1/admin/presentations/{id}/hire
     * Hire candidate from presentation.
     */
    public function hire(Request $request, CandidatePresentation $presentation): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'outcome_id' => ['nullable', 'uuid', 'exists:interview_outcomes,id'],
        ]);

        if ($presentation->isHired()) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate is already hired',
            ], 422);
        }

        if ($presentation->isRejected()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot hire a rejected candidate',
            ], 422);
        }

        $startDate = isset($data['start_date']) ? new \DateTime($data['start_date']) : null;

        $this->service->hireFromPresentation(
            $presentation,
            $startDate,
            $data['outcome_id'] ?? null
        );

        // Notify candidate of hire
        if ($presentation->poolCandidate) {
            $companyName = $presentation->talentRequest?->company?->company_name;
            $this->notificationService->notifyStatusChange(
                $presentation->poolCandidate,
                'hired',
                [
                    'company_name' => $companyName,
                    'presentation_id' => $presentation->id,
                    'start_date' => $data['start_date'] ?? null,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Candidate hired successfully',
            'data' => $presentation->fresh()->load(['talentRequest', 'poolCandidate']),
        ]);
    }

    /**
     * GET /v1/admin/presentations/stats
     * Get presentation statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $baseQuery = CandidatePresentation::whereBetween('presented_at', [$from, $to]);

        $total = (clone $baseQuery)->count();
        $hired = (clone $baseQuery)->hired()->count();
        $rejected = (clone $baseQuery)->status(CandidatePresentation::STATUS_REJECTED)->count();
        $pending = (clone $baseQuery)->pending()->count();

        $byStatus = (clone $baseQuery)
            ->select('presentation_status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('presentation_status')
            ->pluck('count', 'presentation_status')
            ->toArray();

        // Avg client score
        $avgScore = (clone $baseQuery)
            ->whereNotNull('client_score')
            ->avg('client_score');

        // By source channel (join with pool_candidates)
        $bySource = (clone $baseQuery)
            ->join('pool_candidates', 'candidate_presentations.pool_candidate_id', '=', 'pool_candidates.id')
            ->select('pool_candidates.source_channel')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN presentation_status = ? THEN 1 ELSE 0 END) as hired', [CandidatePresentation::STATUS_HIRED])
            ->groupBy('pool_candidates.source_channel')
            ->get()
            ->map(fn($row) => [
                'source_channel' => $row->source_channel,
                'total' => $row->total,
                'hired' => $row->hired,
                'hire_rate_pct' => $row->total > 0 ? round(($row->hired / $row->total) * 100, 1) : 0,
            ])
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'range' => ['from' => $from, 'to' => $to],
                'total' => $total,
                'hired' => $hired,
                'rejected' => $rejected,
                'pending' => $pending,
                'hire_rate_pct' => $total > 0 ? round(($hired / $total) * 100, 1) : null,
                'by_status' => $byStatus,
                'avg_client_score' => $avgScore ? round($avgScore, 1) : null,
                'by_source_channel' => $bySource,
            ],
        ]);
    }
}
