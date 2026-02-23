<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\Vessel;
use App\Services\Fleet\CrewHistorySnapshotService;
use App\Services\Fleet\CrewPlanningService;
use App\Services\Fleet\CrewSynergyEngineV2;
use App\Services\Fleet\CrewSynergyService;
use App\Services\Fleet\FleetRiskOverview;
use App\Services\Fleet\VesselRiskAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FleetController extends Controller
{
    public function overview(FleetRiskOverview $overview): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $overview->compute(),
        ]);
    }

    public function vesselRiskMap(string $id, VesselRiskAggregator $aggregator): JsonResponse
    {
        $vessel = Vessel::findOrFail($id);
        $result = $aggregator->compute($id);

        if (!$result) {
            return response()->json([
                'success' => true,
                'data' => [
                    'vessel_id' => $id,
                    'vessel_name' => $vessel->name,
                    'vessel_imo' => $vessel->imo,
                    'enabled' => (bool) config('maritime.vessel_risk_v1'),
                    'crew_count' => 0,
                    'message' => !config('maritime.vessel_risk_v1')
                        ? 'Feature disabled'
                        : 'No active crew with trust profiles',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => array_merge($result, [
                'vessel_name' => $vessel->name,
                'vessel_imo' => $vessel->imo,
            ]),
        ]);
    }

    public function crewSynergy(Request $request, string $id, CrewSynergyService $synergy): JsonResponse
    {
        $vesselId = $request->query('vessel_id');

        if (!$vesselId) {
            return response()->json([
                'success' => false,
                'message' => 'vessel_id query parameter is required',
            ], 422);
        }

        $result = $synergy->compute($id, $vesselId);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate or vessel not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Fleet-wide gap analysis: upcoming crew rotations.
     */
    public function crewGaps(Request $request, CrewPlanningService $planning): JsonResponse
    {
        $daysAhead = min((int) $request->input('days', 90), 365);
        $gaps = $planning->fleetGapAnalysis($daysAhead);

        return response()->json([
            'success' => true,
            'data' => $gaps,
        ]);
    }

    /**
     * Vessel-specific gap analysis with crew details.
     */
    public function vesselGaps(string $id, Request $request, CrewPlanningService $planning): JsonResponse
    {
        $daysAhead = min((int) $request->input('days', 90), 365);
        $result = $planning->getUpcomingGaps($id, $daysAhead);

        if (!$result['vessel']) {
            return response()->json(['success' => false, 'message' => 'Vessel not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Candidate recommendations for a specific role on a vessel.
     */
    public function recommendCandidates(string $id, Request $request, CrewPlanningService $planning): JsonResponse
    {
        $rank = $request->input('rank');
        if (!$rank) {
            return response()->json(['success' => false, 'message' => 'rank parameter is required'], 422);
        }

        $limit = min((int) $request->input('limit', 10), 50);
        $recommendations = $planning->recommendCandidates($id, $rank, $limit);

        return response()->json([
            'success' => true,
            'data' => $recommendations,
        ]);
    }

    // ─── Crew Synergy Engine V2 ───────────────────────────────────────────

    /**
     * 4-pillar compatibility analysis for a candidate on a vessel.
     */
    public function candidateCompatibility(
        string $id,
        string $candidateId,
        CrewSynergyEngineV2 $engine
    ): JsonResponse {
        if (!$engine->isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Crew Synergy Engine V2 is not enabled',
            ], 403);
        }

        $result = $engine->computeCompatibility($candidateId, $id);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate or vessel not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Shortlist candidates for a role, ranked by 4-pillar compatibility.
     */
    public function shortlistCandidates(
        string $id,
        Request $request,
        CrewSynergyEngineV2 $engine,
        CrewPlanningService $planning
    ): JsonResponse {
        $rank = $request->input('rank');
        if (!$rank) {
            return response()->json(['success' => false, 'message' => 'rank parameter is required'], 422);
        }

        $limit = min((int) $request->input('limit', 10), 50);

        // Fallback to v1 if v2 disabled
        if (!$engine->isEnabled()) {
            return response()->json([
                'success' => true,
                'data' => $planning->recommendCandidates($id, $rank, $limit),
                'version' => 'v1',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $engine->shortlistCandidates($id, $rank, $limit),
            'version' => 'v2',
        ]);
    }

    /**
     * Vessel gaps enriched with top-3 shortlisted candidates per gap rank.
     */
    public function vesselGapsV2(
        string $id,
        Request $request,
        CrewPlanningService $planning,
        CrewSynergyEngineV2 $engine
    ): JsonResponse {
        $daysAhead = min((int) $request->input('days', 90), 365);
        $result = $planning->getUpcomingGaps($id, $daysAhead);

        if (!$result['vessel']) {
            return response()->json(['success' => false, 'message' => 'Vessel not found'], 404);
        }

        // Fallback to v1 if v2 disabled
        if (!$engine->isEnabled()) {
            return response()->json([
                'success' => true,
                'data' => $result,
                'version' => 'v1',
            ]);
        }

        // Enrich each gap with top 3 shortlisted candidates
        foreach ($result['gaps'] as &$gap) {
            $gap['shortlist'] = $engine->shortlistCandidates($id, $gap['rank_code'], 3);
        }
        unset($gap);

        return response()->json([
            'success' => true,
            'data' => $result,
            'version' => 'v2',
        ]);
    }

    /**
     * Crew roster history snapshots for a vessel.
     */
    public function vesselCrewHistory(
        string $id,
        Request $request,
        CrewHistorySnapshotService $snapshotService
    ): JsonResponse {
        $months = min((int) $request->input('months', 6), 24);
        $history = $snapshotService->getHistory($id, $months);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * GET /v1/octo-admin/fleet/availability-insights
     * Filters: availability_status, days_to_available_min, days_to_available_max, contract_end_month
     */
    public function availabilityInsights(Request $request): JsonResponse
    {
        $query = \App\Models\PoolCandidate::query()
            ->where('seafarer', true)
            ->whereNotNull('email_verified_at')
            ->where('is_demo', false);

        // Filter: availability_status
        if ($request->filled('availability_status')) {
            $query->where('availability_status', $request->query('availability_status'));
        }

        // Filter: days_to_available range
        if ($request->filled('days_to_available_min') || $request->filled('days_to_available_max')) {
            $query->whereNotNull('contract_end_estimate');
            $now = now()->startOfDay();

            if ($request->filled('days_to_available_min')) {
                $minDate = $now->copy()->addDays((int) $request->query('days_to_available_min'));
                $query->where('contract_end_estimate', '>=', $minDate);
            }
            if ($request->filled('days_to_available_max')) {
                $maxDate = $now->copy()->addDays((int) $request->query('days_to_available_max'));
                $query->where('contract_end_estimate', '<=', $maxDate);
            }
        }

        // Filter: contract_end_month (format: YYYY-MM)
        if ($request->filled('contract_end_month')) {
            $month = $request->query('contract_end_month');
            $query->whereNotNull('contract_end_estimate')
                ->whereRaw("DATE_FORMAT(contract_end_estimate, '%Y-%m') = ?", [$month]);
        }

        $total = $query->count();
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(10, (int) $request->query('per_page', 20)));

        $candidates = $query->orderBy('contract_end_estimate')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get(['id', 'first_name', 'last_name', 'email', 'nationality',
                   'availability_status', 'contract_end_estimate', 'source_company_id', 'source_label']);

        // Summary stats
        $statusCounts = \App\Models\PoolCandidate::query()
            ->where('seafarer', true)
            ->whereNotNull('email_verified_at')
            ->where('is_demo', false)
            ->selectRaw("availability_status, COUNT(*) as cnt")
            ->groupBy('availability_status')
            ->pluck('cnt', 'availability_status');

        return response()->json([
            'success' => true,
            'data' => [
                'candidates' => $candidates->map(fn($c) => [
                    'id' => $c->id,
                    'name' => trim($c->first_name . ' ' . $c->last_name),
                    'email' => $c->email,
                    'nationality' => $c->nationality,
                    'availability_status' => $c->availability_status,
                    'contract_end_estimate' => $c->contract_end_estimate?->toDateString(),
                    'source_label' => $c->source_label,
                ]),
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'status_counts' => $statusCounts,
            ],
        ]);
    }
}
