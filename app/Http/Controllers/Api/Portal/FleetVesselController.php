<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\FleetVessel;
use App\Models\VesselRegistryCache;
use App\Services\Fleet\ManualRegistryProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FleetVesselController extends Controller
{
    /**
     * GET /v1/portal/vessels
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $vessels = FleetVessel::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(FleetVessel $v) => $this->formatVessel($v));

        return response()->json(['success' => true, 'data' => $vessels]);
    }

    /**
     * POST /v1/portal/vessels
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'imo' => 'nullable|string|max:20',
            'name' => 'required|string|max:255',
            'flag' => 'nullable|string|max:80',
            'vessel_type' => 'nullable|string|max:100',
            'crew_size' => 'nullable|integer|min:0|max:500',
            'status' => 'nullable|string|in:active,inactive',
            'meta' => 'nullable|array',
        ]);

        // Unique IMO per company check
        if (!empty($data['imo'])) {
            $exists = FleetVessel::where('company_id', $companyId)
                ->where('imo', $data['imo'])
                ->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'A vessel with this IMO already exists in your fleet.',
                ], 409);
            }
        }

        $vessel = FleetVessel::create(array_merge($data, ['company_id' => $companyId]));

        // Upsert IMO cache if imo provided
        if (!empty($data['imo'])) {
            (new ManualRegistryProvider())->upsertFromManual($data['imo'], [
                'name' => $data['name'],
                'flag' => $data['flag'] ?? null,
                'vessel_type' => $data['vessel_type'] ?? null,
            ]);
        }

        return response()->json(['success' => true, 'data' => $this->formatVessel($vessel)], 201);
    }

    /**
     * GET /v1/portal/vessels/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $vessel = $this->findOwnedVessel($request->user()->company_id, $id);
        if (!$vessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $this->formatVessel($vessel)]);
    }

    /**
     * PUT /v1/portal/vessels/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $vessel = $this->findOwnedVessel($request->user()->company_id, $id);
        if (!$vessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $data = $request->validate([
            'imo' => 'nullable|string|max:20',
            'name' => 'sometimes|required|string|max:255',
            'flag' => 'nullable|string|max:80',
            'vessel_type' => 'nullable|string|max:100',
            'crew_size' => 'nullable|integer|min:0|max:500',
            'status' => 'nullable|string|in:active,inactive',
            'meta' => 'nullable|array',
        ]);

        // IMO uniqueness check if changed
        if (isset($data['imo']) && $data['imo'] !== $vessel->imo && !empty($data['imo'])) {
            $exists = FleetVessel::where('company_id', $request->user()->company_id)
                ->where('imo', $data['imo'])
                ->where('id', '!=', $vessel->id)
                ->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'A vessel with this IMO already exists in your fleet.',
                ], 409);
            }
        }

        $vessel->update($data);

        // Upsert IMO cache
        if (!empty($data['imo'])) {
            (new ManualRegistryProvider())->upsertFromManual($data['imo'], [
                'name' => $vessel->name,
                'flag' => $vessel->flag,
                'vessel_type' => $vessel->vessel_type,
            ]);
        }

        return response()->json(['success' => true, 'data' => $this->formatVessel($vessel->fresh())]);
    }

    /**
     * DELETE /v1/portal/vessels/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $vessel = $this->findOwnedVessel($request->user()->company_id, $id);
        if (!$vessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $vessel->delete();

        return response()->json(['success' => true, 'message' => 'Vessel removed.']);
    }

    /**
     * GET /v1/portal/vessel-registry/lookup?imo=...
     */
    public function registryLookup(Request $request): JsonResponse
    {
        $imo = $request->query('imo');
        if (!$imo) {
            return response()->json(['success' => true, 'data' => null]);
        }

        $cached = VesselRegistryCache::where('imo', $imo)->first();
        if (!$cached) {
            return response()->json(['success' => true, 'data' => null]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'imo' => $cached->imo,
                'name' => $cached->name,
                'flag' => $cached->flag,
                'vessel_type' => $cached->vessel_type,
                'year_built' => $cached->year_built,
            ],
        ]);
    }

    // ── Helpers ──

    private function findOwnedVessel(string $companyId, string $vesselId): ?FleetVessel
    {
        return FleetVessel::where('company_id', $companyId)->where('id', $vesselId)->first();
    }

    private function formatVessel(FleetVessel $v): array
    {
        return [
            'id' => $v->id,
            'company_id' => $v->company_id,
            'imo' => $v->imo,
            'name' => $v->name,
            'flag' => $v->flag,
            'vessel_type' => $v->vessel_type,
            'crew_size' => $v->crew_size,
            'status' => $v->status,
            'meta' => $v->meta,
            'created_at' => $v->created_at?->toIso8601String(),
        ];
    }
}
