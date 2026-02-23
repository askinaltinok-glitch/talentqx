<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\FleetVessel;
use App\Services\Fleet\CrewPlanningMetricsService;
use App\Services\Fleet\PortalCrewPlanningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrewAnalysisController extends Controller
{
    public function __construct(
        private PortalCrewPlanningService $crewPlanning,
    ) {}

    /**
     * GET /v1/portal/vessels/{id}/crew-analysis
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $vessel = FleetVessel::where('company_id', $request->user()->company_id)
            ->where('id', $id)
            ->first();

        if (!$vessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $analysis = $this->crewPlanning->analyseVessel($vessel);

        // Add candidate recommendations per open/upcoming gap
        $recommendations = [];
        $highFitCount = 0;

        foreach ($analysis['gaps'] as $gap) {
            if ($gap['open_count'] > 0 || in_array($gap['urgency_bucket'], ['30d', '60d'])) {
                $candidates = $this->crewPlanning->recommendForGap(
                    vessel: $vessel,
                    rankCode: $gap['rank_code'],
                    limit: 5,
                    urgencyBucket: $gap['urgency_bucket'],
                );
                $recommendations[$gap['rank_code']] = $candidates;

                // Count high-fit candidates
                $highFitCount += collect($candidates)->where('fit_level', 'high')->count();
            }
        }

        $analysis['summary']['high_fit_count'] = $highFitCount;

        return response()->json([
            'success' => true,
            'data' => array_merge($analysis, [
                'recommendations' => $recommendations,
            ]),
        ]);
    }

    /**
     * GET /v1/portal/vessels/{id}/future-pool?rank_code=...
     */
    public function futurePool(Request $request, string $id): JsonResponse
    {
        $vessel = FleetVessel::where('company_id', $request->user()->company_id)
            ->where('id', $id)
            ->first();

        if (!$vessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $rankCode = $request->query('rank_code', '');

        $pool = $this->crewPlanning->futurePool($vessel, $rankCode, 15);

        return response()->json([
            'success' => true,
            'data' => $pool,
        ]);
    }

    /**
     * GET /v1/portal/crew-kpis
     */
    public function kpis(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $days = (int) $request->query('days', 30);

        $kpis = app(CrewPlanningMetricsService::class)->getCompanyKPIs($companyId, $days);

        return response()->json([
            'success' => true,
            'data' => $kpis,
        ]);
    }
}
