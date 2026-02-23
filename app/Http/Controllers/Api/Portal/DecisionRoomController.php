<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\FleetVessel;
use App\Models\PoolCandidate;
use App\Services\Fleet\DecisionRoomService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DecisionRoomController extends Controller
{
    public function __construct(
        private DecisionRoomService $decisionRoom,
    ) {}

    /**
     * GET /v1/portal/vessels/{vesselId}/decision-room/snapshot
     */
    public function vesselSnapshot(Request $request, string $vesselId): JsonResponse
    {
        $vessel = $this->resolveVessel($request, $vesselId);
        if (!$vessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $snapshot = $this->decisionRoom->getSnapshot($vessel);

        return response()->json(['success' => true, 'data' => $snapshot]);
    }

    /**
     * GET /v1/portal/vessels/{vesselId}/decision-room/shortlist?rank=&limit=
     */
    public function shortlist(Request $request, string $vesselId): JsonResponse
    {
        $vessel = $this->resolveVessel($request, $vesselId);
        if (!$vessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $rank = $request->query('rank', '');
        if (!$rank) {
            return response()->json(['success' => false, 'message' => 'rank parameter is required.'], 422);
        }

        $limit = (int) $request->query('limit', 10);
        $limit = max(1, min(30, $limit));

        $shortlist = $this->decisionRoom->getShortlist($vessel, $rank, $limit);

        return response()->json(['success' => true, 'data' => $shortlist]);
    }

    /**
     * GET /v1/portal/vessels/{vesselId}/decision-room/compatibility/{candidateId}
     */
    public function compatibility(Request $request, string $vesselId, string $candidateId): JsonResponse
    {
        $vessel = $this->resolveVessel($request, $vesselId);
        if (!$vessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $result = $this->decisionRoom->getCompatibility($vessel, $candidateId);

        if (!$result) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Compatibility analysis not available (V2 engine may be disabled or no IMO match).',
            ]);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * POST /v1/portal/vessels/{vesselId}/decision-room/simulate
     * Body: { candidate_ids: [...] }
     */
    public function simulate(Request $request, string $vesselId): JsonResponse
    {
        $vessel = $this->resolveVessel($request, $vesselId);
        if (!$vessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $request->validate([
            'candidate_ids' => 'required|array|min:1|max:20',
            'candidate_ids.*' => 'uuid',
        ]);

        $result = $this->decisionRoom->simulate($vessel, $request->input('candidate_ids'));

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * POST /v1/portal/vessels/{vesselId}/decision-room/decide
     * Body: { candidate_id, rank_code, action, reason? }
     */
    public function decide(Request $request, string $vesselId): JsonResponse
    {
        $vessel = $this->resolveVessel($request, $vesselId);
        if (!$vessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $validated = $request->validate([
            'candidate_id' => 'required|uuid',
            'rank_code' => 'required|string|max:50',
            'action' => 'required|in:shortlisted,selected,confirmed,rejected,deferred',
            'reason' => 'nullable|string|max:1000',
        ]);

        $entry = $this->decisionRoom->logDecision(
            vessel: $vessel,
            userId: $request->user()->id,
            data: $validated,
        );

        return response()->json(['success' => true, 'data' => [
            'id' => $entry->id,
            'action' => $entry->action,
            'candidate_name' => $entry->candidate_name,
            'created_at' => $entry->created_at->toIso8601String(),
        ]], 201);
    }

    /**
     * GET /v1/portal/vessels/{vesselId}/decision-room/history?rank=
     */
    public function history(Request $request, string $vesselId): JsonResponse
    {
        $vessel = $this->resolveVessel($request, $vesselId);
        if (!$vessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $rank = $request->query('rank');
        $history = $this->decisionRoom->getHistory($vessel, $rank);

        return response()->json(['success' => true, 'data' => $history]);
    }

    /**
     * GET /v1/portal/vessels/{vesselId}/decision-room/packet/{candidateId}
     */
    public function downloadDecisionPacket(Request $request, string $vesselId, string $candidateId): Response
    {
        $vessel = $this->resolveVessel($request, $vesselId);
        if (!$vessel) {
            abort(404, 'Vessel not found.');
        }

        $candidate = PoolCandidate::find($candidateId);
        if (!$candidate) {
            abort(404, 'Candidate not found.');
        }

        $compatibility = $this->decisionRoom->getCompatibility($vessel, $candidateId);

        // Get most recent simulation for this vessel if available
        $recentSimulation = null;
        $recentEntry = \App\Models\DecisionRoomEntry::withoutTenantScope()
            ->where('fleet_vessel_id', $vessel->id)
            ->where('candidate_id', $candidateId)
            ->whereNotNull('simulation_snapshot')
            ->orderByDesc('created_at')
            ->first();
        if ($recentEntry) {
            $recentSimulation = $recentEntry->simulation_snapshot;
        }

        $pdf = Pdf::loadView('pdf.decision-room-packet', [
            'vessel' => $vessel,
            'candidate' => $candidate,
            'compatibility' => $compatibility,
            'simulation' => $recentSimulation,
            'generatedAt' => now()->toIso8601String(),
        ]);

        $filename = sprintf(
            'decision-room-%s-%s-%s.pdf',
            str_replace(' ', '-', $vessel->name),
            str_replace(' ', '-', $candidate->first_name . '-' . $candidate->last_name),
            now()->format('Ymd')
        );

        return $pdf->download($filename);
    }

    // ─── Private ──────────────────────────────────────────────────

    private function resolveVessel(Request $request, string $vesselId): ?FleetVessel
    {
        return FleetVessel::where('company_id', $request->user()->company_id)
            ->where('id', $vesselId)
            ->first();
    }
}
