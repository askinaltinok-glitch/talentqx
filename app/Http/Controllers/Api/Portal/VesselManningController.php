<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\FleetVessel;
use App\Models\VesselManningRequirement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VesselManningController extends Controller
{
    private const ALLOWED_RANKS = [
        'master', 'chief_officer', 'second_officer', 'third_officer',
        'chief_engineer', 'second_engineer', 'third_engineer',
        'electrical_officer', 'bosun', 'ab_seaman', 'oiler', 'cook',
    ];

    private const ENGLISH_LEVELS = [
        'basic', 'intermediate', 'advanced', 'fluent', 'native',
    ];

    /**
     * GET /v1/portal/vessels/{id}/manning
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $vessel = $this->mustOwnVessel($request->user()->company_id, $id);
        if (!$vessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $requirements = $vessel->manningRequirements()
            ->orderByRaw("FIELD(rank_code, '" . implode("','", self::ALLOWED_RANKS) . "')")
            ->get()
            ->map(fn(VesselManningRequirement $r) => [
                'id' => $r->id,
                'rank_code' => $r->rank_code,
                'required_count' => $r->required_count,
                'required_certs' => $r->required_certs ?? [],
                'min_english_level' => $r->min_english_level,
                'notes' => $r->notes,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'vessel_id' => $vessel->id,
                'vessel_name' => $vessel->name,
                'requirements' => $requirements,
                'allowed_ranks' => self::ALLOWED_RANKS,
                'english_levels' => self::ENGLISH_LEVELS,
            ],
        ]);
    }

    /**
     * PUT /v1/portal/vessels/{id}/manning  (bulk replace)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $vessel = $this->mustOwnVessel($request->user()->company_id, $id);
        if (!$vessel) {
            return response()->json(['success' => false, 'message' => 'Vessel not found.'], 404);
        }

        $data = $request->validate([
            'requirements' => 'required|array',
            'requirements.*.rank_code' => 'required|string|in:' . implode(',', self::ALLOWED_RANKS),
            'requirements.*.required_count' => 'required|integer|min:0|max:50',
            'requirements.*.required_certs' => 'nullable|array',
            'requirements.*.required_certs.*' => 'string|max:100',
            'requirements.*.min_english_level' => 'nullable|string|in:' . implode(',', self::ENGLISH_LEVELS),
            'requirements.*.notes' => 'nullable|string|max:500',
        ]);

        // Delete all existing + re-insert (bulk replace)
        $vessel->manningRequirements()->delete();

        $created = [];
        foreach ($data['requirements'] as $req) {
            if ($req['required_count'] <= 0) continue;

            $created[] = VesselManningRequirement::create([
                'vessel_id' => $vessel->id,
                'rank_code' => $req['rank_code'],
                'required_count' => $req['required_count'],
                'required_certs' => $req['required_certs'] ?? null,
                'min_english_level' => $req['min_english_level'] ?? null,
                'notes' => $req['notes'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'vessel_id' => $vessel->id,
                'count' => count($created),
            ],
        ]);
    }

    private function mustOwnVessel(string $companyId, string $vesselId): ?FleetVessel
    {
        return FleetVessel::where('company_id', $companyId)->where('id', $vesselId)->first();
    }
}
