<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PoolCompany;
use App\Models\TalentRequest;
use App\Services\ConsumptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TalentRequestController extends Controller
{
    public function __construct(
        private ConsumptionService $service
    ) {}

    /**
     * GET /v1/admin/talent-requests
     * List all talent requests.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TalentRequest::query()
            ->with('company:id,company_name,industry')
            ->withCount('presentations');

        // Filters
        if ($request->filled('status')) {
            $query->status($request->input('status'));
        }
        if ($request->filled('industry')) {
            $query->industry($request->input('industry'));
        }
        if ($request->filled('company_id')) {
            $query->where('pool_company_id', $request->input('company_id'));
        }
        if ($request->filled('position_code')) {
            $query->where('position_code', 'like', '%' . $request->input('position_code') . '%');
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $paginated = $query->orderByDesc('created_at')->paginate($perPage);

        $data = $paginated->getCollection()->map(function ($request) {
            return [
                ...$request->toArray(),
                'fill_rate' => $request->fill_rate,
                'conversion_rate' => $request->conversion_rate,
            ];
        });

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
     * POST /v1/admin/talent-requests
     * Create a new talent request.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pool_company_id' => ['required', 'uuid', 'exists:pool_companies,id'],
            'position_code' => ['required', 'string', 'max:128'],
            'industry_code' => ['nullable', 'string', 'max:64'],
            'required_count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'english_required' => ['nullable', 'boolean'],
            'min_english_level' => ['nullable', 'string', 'in:A1,A2,B1,B2,C1,C2'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'min_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'required_competencies' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'meta' => ['nullable', 'array'],
        ]);

        $company = PoolCompany::findOrFail($data['pool_company_id']);
        $talentRequest = $this->service->createTalentRequest($company, $data);

        return response()->json([
            'success' => true,
            'message' => 'Talent request created successfully',
            'data' => $talentRequest->load('company:id,company_name'),
        ], 201);
    }

    /**
     * GET /v1/admin/talent-requests/{id}
     * Get talent request details.
     */
    public function show(TalentRequest $talentRequest): JsonResponse
    {
        $talentRequest->load([
            'company',
            'presentations' => function ($q) {
                $q->with('poolCandidate:id,first_name,last_name,email,primary_industry,english_level_self')
                    ->orderByDesc('presented_at');
            },
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                ...$talentRequest->toArray(),
                'fill_rate' => $talentRequest->fill_rate,
                'conversion_rate' => $talentRequest->conversion_rate,
            ],
        ]);
    }

    /**
     * PUT /v1/admin/talent-requests/{id}
     * Update talent request.
     */
    public function update(Request $request, TalentRequest $talentRequest): JsonResponse
    {
        $data = $request->validate([
            'required_count' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'english_required' => ['sometimes', 'boolean'],
            'min_english_level' => ['nullable', 'string', 'in:A1,A2,B1,B2,C1,C2'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'min_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'required_competencies' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', 'string', 'in:open,matching,fulfilled,closed'],
        ]);

        $talentRequest->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Talent request updated successfully',
            'data' => $talentRequest->fresh(),
        ]);
    }

    /**
     * POST /v1/admin/talent-requests/{id}/close
     * Close a talent request.
     */
    public function close(TalentRequest $talentRequest): JsonResponse
    {
        if ($talentRequest->status === TalentRequest::STATUS_CLOSED) {
            return response()->json([
                'success' => false,
                'message' => 'Request is already closed',
            ], 422);
        }

        $talentRequest->close();

        return response()->json([
            'success' => true,
            'message' => 'Talent request closed',
            'data' => $talentRequest->fresh(),
        ]);
    }

    /**
     * GET /v1/admin/talent-requests/{id}/matching-candidates
     * Get candidates matching this request's criteria.
     */
    public function matchingCandidates(Request $request, TalentRequest $talentRequest): JsonResponse
    {
        $limit = min((int) $request->input('limit', 20), 50);
        $ranked = $request->boolean('ranked', false);

        if ($ranked) {
            // Use smart matching with scoring
            $matches = $this->service->findBestMatches($talentRequest, $limit);

            $data = collect($matches)->map(function ($match) {
                $candidate = $match['candidate'];
                $interview = $match['interview'];

                return [
                    'id' => $candidate->id,
                    'first_name' => $candidate->first_name,
                    'last_name' => $candidate->last_name,
                    'email' => $candidate->email,
                    'primary_industry' => $candidate->primary_industry,
                    'english_level_self' => $candidate->english_level_self,
                    'source_channel' => $candidate->source_channel,
                    'seafarer' => $candidate->seafarer,
                    'last_assessed_at' => $candidate->last_assessed_at?->toIso8601String(),
                    'score' => $interview?->calibrated_score ?? $interview?->final_score,
                    'decision' => $interview?->decision,
                    'english_assessment_status' => $interview?->english_assessment_status,
                    'has_video' => (bool) $interview?->video_assessment_url,
                    'match_score' => $match['match_score'],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'count' => count($matches),
                    'request_id' => $talentRequest->id,
                    'ranking_mode' => 'smart',
                ],
            ]);
        }

        // Simple matching
        $candidates = $this->service->findMatchingCandidates($talentRequest, $limit);

        $data = $candidates->map(function ($candidate) {
            $interview = $candidate->formInterviews->first();
            return [
                'id' => $candidate->id,
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'primary_industry' => $candidate->primary_industry,
                'english_level_self' => $candidate->english_level_self,
                'source_channel' => $candidate->source_channel,
                'seafarer' => $candidate->seafarer,
                'last_assessed_at' => $candidate->last_assessed_at?->toIso8601String(),
                'score' => $interview?->calibrated_score ?? $interview?->final_score,
                'decision' => $interview?->decision,
                'english_assessment_status' => $interview?->english_assessment_status,
                'has_video' => (bool) $interview?->video_assessment_url,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'count' => $candidates->count(),
                'request_id' => $talentRequest->id,
                'ranking_mode' => 'simple',
            ],
        ]);
    }

    /**
     * POST /v1/admin/talent-requests/{id}/present
     * Present candidates to company.
     */
    public function presentCandidates(Request $request, TalentRequest $talentRequest): JsonResponse
    {
        $data = $request->validate([
            'candidate_ids' => ['required', 'array', 'min:1'],
            'candidate_ids.*' => ['required', 'uuid', 'exists:pool_candidates,id'],
        ]);

        if ($talentRequest->status === TalentRequest::STATUS_CLOSED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot present to a closed request',
            ], 422);
        }

        $presentations = $this->service->presentCandidates($talentRequest, $data['candidate_ids']);

        return response()->json([
            'success' => true,
            'message' => count($presentations) . ' candidate(s) presented successfully',
            'data' => [
                'presented_count' => count($presentations),
                'presentation_ids' => array_map(fn($p) => $p->id, $presentations),
            ],
        ], 201);
    }

    /**
     * GET /v1/admin/talent-requests/stats
     * Get talent request statistics.
     */
    public function stats(): JsonResponse
    {
        $total = TalentRequest::count();
        $byStatus = TalentRequest::select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $byIndustry = TalentRequest::select('industry_code')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('industry_code')
            ->pluck('count', 'industry_code')
            ->toArray();

        $avgFillRate = TalentRequest::whereIn('status', ['fulfilled', 'closed'])
            ->selectRaw('AVG(hired_count * 100.0 / NULLIF(required_count, 0)) as avg_fill')
            ->value('avg_fill');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'by_status' => $byStatus,
                'by_industry' => $byIndustry,
                'avg_fill_rate_pct' => $avgFillRate ? round($avgFillRate, 1) : null,
            ],
        ]);
    }
}
