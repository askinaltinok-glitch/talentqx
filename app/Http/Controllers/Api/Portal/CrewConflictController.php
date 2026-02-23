<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\CrewConflictReport;
use App\Models\FleetVessel;
use App\Models\Vessel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrewConflictController extends Controller
{
    /**
     * POST /v1/portal/vessels/{vesselId}/conflicts
     */
    public function store(Request $request, string $vesselId): JsonResponse
    {
        $fleetVessel = FleetVessel::where('company_id', $request->user()->company_id)
            ->where('id', $vesselId)
            ->first();

        if (!$fleetVessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $validated = $request->validate([
            'reporter_candidate_id' => 'nullable|uuid',
            'target_candidate_id' => 'nullable|uuid',
            'category' => 'required|in:' . implode(',', CrewConflictReport::VALID_CATEGORIES),
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
            'is_anonymous' => 'boolean',
        ]);

        // Resolve system vessel ID via IMO
        $systemVessel = $fleetVessel->imo
            ? Vessel::where('imo', $fleetVessel->imo)->first()
            : null;
        $resolvedVesselId = $systemVessel?->id ?? $fleetVessel->id;

        // Run anti-abuse detection
        $suspicion = CrewConflictReport::detectSuspicion(
            $validated,
            $request->user()->company_id,
            $resolvedVesselId
        );

        $report = CrewConflictReport::create([
            'company_id' => $request->user()->company_id,
            'vessel_id' => $resolvedVesselId,
            'reporter_candidate_id' => $validated['reporter_candidate_id'],
            'target_candidate_id' => $validated['target_candidate_id'],
            'category' => $validated['category'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'is_anonymous' => $validated['is_anonymous'] ?? false,
            'is_suspicious' => $suspicion['is_suspicious'],
            'suspicion_reason' => $suspicion['suspicion_reason'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $report->id,
                'category' => $report->category,
                'is_suspicious' => $report->is_suspicious,
                'created_at' => $report->created_at->toIso8601String(),
            ],
        ], 201);
    }
}
