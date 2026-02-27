<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePoolCandidateRequest;
use App\Http\Requests\StartCandidateInterviewRequest;
use App\Models\PoolCandidate;
use App\Services\PoolCandidateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PoolCandidateController extends Controller
{
    public function __construct(
        private PoolCandidateService $service
    ) {}

    /**
     * POST /v1/candidates
     *
     * Create a new pool candidate (Candidate Supply Engine).
     */
    public function store(CreatePoolCandidateRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Check if candidate already exists
        $existing = $this->service->findByEmail($data['email']);
        if ($existing) {
            return response()->json([
                'error' => 'Candidate already exists',
                'candidate_id' => $existing->id,
                'status' => $existing->status,
            ], 409);
        }

        $candidate = $this->service->create($data);

        return response()->json([
            'candidate_id' => $candidate->id,
            'status' => $candidate->status,
            'created_at' => $candidate->created_at->toIso8601String(),
        ], 201);
    }

    /**
     * GET /v1/candidates/{id}
     *
     * Get candidate details.
     */
    public function show(PoolCandidate $candidate): JsonResponse
    {
        $candidate->load(['formInterviews' => fn($q) => $q->latest()->limit(5)]);

        return response()->json([
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
            'latest_score' => $candidate->latest_score,
            'latest_decision' => $candidate->latest_decision,
            'interviews' => $candidate->formInterviews->map(fn($i) => [
                'id' => $i->id,
                'status' => $i->status,
                'position_code' => $i->position_code,
                'industry_code' => $i->industry_code,
                'final_score' => $i->calibrated_score ?? $i->final_score,
                'decision' => $i->decision,
                'created_at' => $i->created_at->toIso8601String(),
                'completed_at' => $i->completed_at?->toIso8601String(),
            ]),
            'created_at' => $candidate->created_at->toIso8601String(),
        ]);
    }

    /**
     * POST /v1/candidates/{id}/start-interview
     *
     * Start an interview for a pool candidate.
     */
    public function startInterview(
        StartCandidateInterviewRequest $request,
        PoolCandidate $candidate
    ): JsonResponse {
        $data = $request->validated();
        $regulation = $request->getRegulation();

        // Check candidate status
        if (in_array($candidate->status, [PoolCandidate::STATUS_HIRED, PoolCandidate::STATUS_ARCHIVED])) {
            return response()->json([
                'error' => 'Cannot start interview for candidate with status: ' . $candidate->status,
            ], 422);
        }

        // Check for active interview
        $activeInterview = $candidate->formInterviews()
            ->whereIn('status', ['draft', 'in_progress'])
            ->first();

        if ($activeInterview) {
            return response()->json([
                'error' => 'Candidate has an active interview',
                'interview_id' => $activeInterview->id,
                'interview_status' => $activeInterview->status,
            ], 409);
        }

        $interview = $this->service->startInterview(
            candidate: $candidate,
            positionCode: $data['position_code'] ?? '__generic__',
            industryCode: $data['industry_code'] ?? 'general',
            consents: $data['consents'],
            countryCode: $data['country_code'],
            regulation: $regulation,
            request: $request,
            companyId: $data['company_id'] ?? null
        );

        return response()->json([
            'interview_id' => $interview->id,
            'candidate_id' => $candidate->id,
            'status' => $interview->status,
            'position_code' => $interview->position_code,
            'industry_code' => $interview->industry_code,
            'regulation' => $regulation,
            'consents_recorded' => true,
            'created_at' => $interview->created_at->toIso8601String(),
        ], 201);
    }

    /**
     * POST /v1/candidates/{id}/present
     *
     * Mark candidate as presented to company.
     */
    public function present(PoolCandidate $candidate): JsonResponse
    {
        if ($candidate->status !== PoolCandidate::STATUS_IN_POOL) {
            return response()->json([
                'error' => 'Only candidates in pool can be presented',
                'current_status' => $candidate->status,
            ], 422);
        }

        $candidate->markAsPresented();

        return response()->json([
            'candidate_id' => $candidate->id,
            'status' => $candidate->fresh()->status,
            'presented_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * POST /v1/candidates/{id}/hire
     *
     * Mark candidate as hired.
     */
    public function hire(PoolCandidate $candidate): JsonResponse
    {
        if (!in_array($candidate->status, [
            PoolCandidate::STATUS_IN_POOL,
            PoolCandidate::STATUS_PRESENTED,
        ])) {
            return response()->json([
                'error' => 'Only pool or presented candidates can be hired',
                'current_status' => $candidate->status,
            ], 422);
        }

        $candidate->markAsHired();

        return response()->json([
            'candidate_id' => $candidate->id,
            'status' => $candidate->fresh()->status,
            'hired_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * GET /v1/candidates/pool
     *
     * Get candidates in pool with filters.
     */
    public function pool(Request $request): JsonResponse
    {
        $filters = $request->only([
            'industry',
            'source_channel',
            'english_level',
            'min_english_level',
            'seafarer',
            'sort_by',
            'sort_dir',
        ]);

        $perPage = min((int) $request->input('per_page', 25), 100);
        $candidates = $this->service->getPoolCandidates($filters, $perPage);

        return response()->json([
            'data' => $candidates->items(),
            'pagination' => [
                'current_page' => $candidates->currentPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total(),
                'last_page' => $candidates->lastPage(),
            ],
        ]);
    }

    /**
     * GET /v1/candidates/stats
     *
     * Get pool statistics.
     */
    public function stats(): JsonResponse
    {
        return response()->json($this->service->getPoolStats());
    }
}
