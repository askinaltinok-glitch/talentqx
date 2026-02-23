<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\FleetVessel;
use App\Models\VesselAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VesselRosterController extends Controller
{
    /**
     * GET /v1/portal/vessels/{id}/roster
     */
    public function index(Request $request, string $id): JsonResponse
    {
        $vessel = $this->mustOwnVessel($request->user()->company_id, $id);
        if (!$vessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $assignments = $vessel->assignments()
            ->with('candidate:id,first_name,last_name,nationality,email')
            ->orderBy('contract_start_at')
            ->get()
            ->map(fn(VesselAssignment $a) => $this->formatAssignment($a));

        $grouped = [
            'onboard' => $assignments->where('status', 'onboard')->values(),
            'planned' => $assignments->where('status', 'planned')->values(),
            'completed' => $assignments->where('status', 'completed')->values(),
            'terminated_early' => $assignments->where('status', 'terminated_early')->values(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'vessel_id' => $vessel->id,
                'vessel_name' => $vessel->name,
                'roster' => $grouped,
                'total' => $assignments->count(),
            ],
        ]);
    }

    /**
     * POST /v1/portal/vessels/{id}/roster
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $vessel = $this->mustOwnVessel($request->user()->company_id, $id);
        if (!$vessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $data = $request->validate([
            'candidate_id' => 'required|uuid|exists:pool_candidates,id',
            'rank_code' => 'required|string|max:50',
            'contract_start_at' => 'required|date',
            'contract_end_at' => 'required|date|after:contract_start_at',
            'status' => 'nullable|string|in:planned,onboard',
        ]);

        // Check duplicate assignment
        $exists = VesselAssignment::where('vessel_id', $vessel->id)
            ->where('candidate_id', $data['candidate_id'])
            ->where('contract_start_at', $data['contract_start_at'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This crew member is already assigned for this period.',
            ], 409);
        }

        $assignment = VesselAssignment::create([
            'vessel_id' => $vessel->id,
            'candidate_id' => $data['candidate_id'],
            'rank_code' => $data['rank_code'],
            'contract_start_at' => $data['contract_start_at'],
            'contract_end_at' => $data['contract_end_at'],
            'status' => $data['status'] ?? 'planned',
        ]);

        // Record time-to-fill KPI
        try {
            app(\App\Services\Fleet\CrewPlanningMetricsService::class)
                ->recordTimeToFill($assignment);
        } catch (\Throwable $e) {
            // KPI recording is non-critical
        }

        $assignment->load('candidate:id,first_name,last_name,nationality,email');

        return response()->json([
            'success' => true,
            'data' => $this->formatAssignment($assignment),
        ], 201);
    }

    /**
     * PUT /v1/portal/roster/{assignmentId}
     */
    public function update(Request $request, string $assignmentId): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $assignment = $this->findOwnedAssignment($companyId, $assignmentId);
        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Assignment not found.'], 404);
        }

        $data = $request->validate([
            'contract_start_at' => 'sometimes|date',
            'contract_end_at' => 'sometimes|date',
            'status' => 'sometimes|string|in:planned,onboard,completed,terminated_early',
            'termination_reason' => 'nullable|string|max:500',
        ]);

        if (isset($data['status']) && $data['status'] === 'terminated_early') {
            $data['ended_early_at'] = now();
        }

        $assignment->update($data);
        $assignment->load('candidate:id,first_name,last_name,nationality,email');

        return response()->json([
            'success' => true,
            'data' => $this->formatAssignment($assignment),
        ]);
    }

    /**
     * DELETE /v1/portal/roster/{assignmentId}
     */
    public function destroy(Request $request, string $assignmentId): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $assignment = $this->findOwnedAssignment($companyId, $assignmentId);
        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Assignment not found.'], 404);
        }

        $assignment->delete();

        return response()->json(['success' => true, 'message' => 'Assignment removed.']);
    }

    /**
     * GET /v1/portal/candidates/search?q=...&scope=private|global
     */
    public function searchCandidates(Request $request): JsonResponse
    {
        $q = $request->query('q', '');
        $scope = $request->query('scope', 'private');
        $companyId = $request->user()->company_id;

        $query = \App\Models\PoolCandidate::query()
            ->where('seafarer', true)
            ->whereNotNull('email_verified_at')
            ->where('is_demo', false);

        if ($scope === 'private') {
            $query->where('source_company_id', $companyId);
        }

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('first_name', 'LIKE', "%{$q}%")
                    ->orWhere('last_name', 'LIKE', "%{$q}%")
                    ->orWhere('email', 'LIKE', "%{$q}%");
            });
        }

        $results = $query->limit(20)
            ->get(['id', 'first_name', 'last_name', 'email', 'nationality'])
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => trim($c->first_name . ' ' . $c->last_name),
                'email' => $c->email,
                'nationality' => $c->nationality,
            ]);

        return response()->json(['success' => true, 'data' => $results]);
    }

    // ── Helpers ──

    private function mustOwnVessel(string $companyId, string $vesselId): ?FleetVessel
    {
        return FleetVessel::where('company_id', $companyId)->where('id', $vesselId)->first();
    }

    private function findOwnedAssignment(string $companyId, string $assignmentId): ?VesselAssignment
    {
        return VesselAssignment::whereHas('vessel', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->where('id', $assignmentId)->first();
    }

    private function formatAssignment(VesselAssignment $a): array
    {
        return [
            'id' => $a->id,
            'vessel_id' => $a->vessel_id,
            'candidate_id' => $a->candidate_id,
            'candidate_name' => $a->candidate
                ? trim($a->candidate->first_name . ' ' . $a->candidate->last_name)
                : null,
            'candidate_nationality' => $a->candidate?->nationality,
            'rank_code' => $a->rank_code,
            'contract_start_at' => $a->contract_start_at?->toDateString(),
            'contract_end_at' => $a->contract_end_at?->toDateString(),
            'days_until_end' => $a->daysUntilEnd(),
            'status' => $a->status,
            'termination_reason' => $a->termination_reason,
            'ended_early_at' => $a->ended_early_at?->toIso8601String(),
        ];
    }
}
