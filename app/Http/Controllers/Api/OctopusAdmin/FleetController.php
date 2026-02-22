<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\Vessel;
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
}
