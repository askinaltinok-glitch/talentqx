<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\CrewOutcome;
use App\Models\FleetVessel;
use App\Models\Vessel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrewOutcomeController extends Controller
{
    /**
     * POST /v1/portal/vessels/{vesselId}/outcomes
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
            'contract_id' => 'nullable|uuid',
            'captain_candidate_id' => 'nullable|uuid',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'outcome_type' => 'required|in:' . implode(',', CrewOutcome::VALID_TYPES),
            'severity' => 'required|integer|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
        ]);

        // Resolve system vessel ID via IMO
        $systemVessel = $fleetVessel->imo
            ? Vessel::where('imo', $fleetVessel->imo)->first()
            : null;

        $outcome = CrewOutcome::create([
            'company_id' => $request->user()->company_id,
            'vessel_id' => $systemVessel?->id ?? $fleetVessel->id,
            'contract_id' => $validated['contract_id'],
            'captain_candidate_id' => $validated['captain_candidate_id'],
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'outcome_type' => $validated['outcome_type'],
            'severity' => $validated['severity'],
            'notes' => $validated['notes'],
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $outcome->id,
                'outcome_type' => $outcome->outcome_type,
                'severity' => $outcome->severity,
                'created_at' => $outcome->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * GET /v1/portal/vessels/{vesselId}/outcomes
     */
    public function index(Request $request, string $vesselId): JsonResponse
    {
        $fleetVessel = FleetVessel::where('company_id', $request->user()->company_id)
            ->where('id', $vesselId)
            ->first();

        if (!$fleetVessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $outcomes = CrewOutcome::withoutTenantScope()
            ->where('company_id', $request->user()->company_id)
            ->where(function ($q) use ($fleetVessel) {
                $q->where('vessel_id', $fleetVessel->id);
                if ($fleetVessel->imo) {
                    $systemVessel = Vessel::where('imo', $fleetVessel->imo)->first();
                    if ($systemVessel) {
                        $q->orWhere('vessel_id', $systemVessel->id);
                    }
                }
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $outcomes->map(fn ($o) => [
                'id' => $o->id,
                'outcome_type' => $o->outcome_type,
                'severity' => $o->severity,
                'period_start' => $o->period_start->toDateString(),
                'period_end' => $o->period_end->toDateString(),
                'notes' => $o->notes,
                'captain_candidate_id' => $o->captain_candidate_id,
                'created_at' => $o->created_at->toIso8601String(),
            ]),
        ]);
    }
}
