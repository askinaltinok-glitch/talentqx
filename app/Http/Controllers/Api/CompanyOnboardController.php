<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyVessel;
use App\Models\Vessel;
use App\Models\CandidateContract;
use App\Models\PoolCandidate;
use App\Services\Fleet\CrewPlanningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyOnboardController extends Controller
{
    /**
     * Screen 2: Add a vessel during onboarding
     */
    public function addVessel(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'vessel_type' => 'required|string|max:100',
            'crew_size' => 'required|integer|min:1|max:500',
            'contract_duration_months' => 'required|integer|min:1|max:36',
        ]);

        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'No company associated'], 422);
        }

        // Create vessel stub
        $vessel = Vessel::create([
            'name' => $request->name,
            'type' => $request->vessel_type,
            'vessel_type_normalized' => Str::snake($request->vessel_type),
            'data_source' => 'manual',
            'imo' => null,
        ]);

        // Create company-vessel pivot record
        CompanyVessel::create([
            'company_id' => $company->id,
            'vessel_id' => $vessel->id,
            'role' => 'operator',
            'is_active' => true,
            'assigned_at' => now(),
        ]);

        // Store onboarding metadata in company settings (crew_size, duration)
        $settings = $company->settings ?? [];
        $settings['onboard_vessels'] = $settings['onboard_vessels'] ?? [];
        $settings['onboard_vessels'][] = [
            'vessel_id' => $vessel->id,
            'crew_size' => $request->crew_size,
            'contract_duration_months' => $request->contract_duration_months,
        ];
        $company->settings = $settings;
        $company->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $vessel->id,
                'name' => $vessel->name,
            ],
        ]);
    }

    /**
     * Screen 3: Set rank requirements for a vessel
     */
    public function setRankRequirements(Request $request, string $vesselId): JsonResponse
    {
        $request->validate([
            'requirements' => 'required|array|min:1',
            'requirements.*.rank_code' => 'required|string|max:50',
            'requirements.*.min_certificates' => 'nullable|array',
            'requirements.*.min_certificates.*' => 'string|max:100',
            'requirements.*.watch_structure' => 'nullable|string|max:100',
        ]);

        $vessel = Vessel::findOrFail($vesselId);

        // Store rank requirements as vessel metadata
        // In a full system this would be a separate table, but for onboarding we store in JSON
        $company = $request->user()->company;
        $settings = $company->settings ?? [];
        $settings['vessel_rank_requirements'] = $settings['vessel_rank_requirements'] ?? [];
        $settings['vessel_rank_requirements'][$vesselId] = $request->requirements;
        $company->settings = $settings;
        $company->save();

        return response()->json(['success' => true]);
    }

    /**
     * Screen 4: Activate compliance monitoring for a vessel
     */
    public function activateCompliance(Request $request, string $vesselId): JsonResponse
    {
        $vessel = Vessel::findOrFail($vesselId);

        $company = $request->user()->company;
        $settings = $company->settings ?? [];
        $settings['compliance_active'] = $settings['compliance_active'] ?? [];
        if (!in_array($vesselId, $settings['compliance_active'])) {
            $settings['compliance_active'][] = $vesselId;
        }
        $settings['compliance_monitors'] = $settings['compliance_monitors'] ?? [];
        $settings['compliance_monitors'][$vesselId] = [
            'certificate_expiry_monitor' => true,
            'crew_availability' => true,
            'contract_timeline' => true,
            'activated_at' => now()->toIso8601String(),
        ];
        $company->settings = $settings;
        $company->save();

        return response()->json(['success' => true]);
    }

    /**
     * Screen 5: Run first crew analysis
     */
    public function runCrewAnalysis(Request $request, string $vesselId): JsonResponse
    {
        $vessel = Vessel::findOrFail($vesselId);
        $company = $request->user()->company;
        $settings = $company->settings ?? [];

        // Get rank requirements for this vessel
        $rankReqs = $settings['vessel_rank_requirements'][$vesselId] ?? [];

        // Check existing crew (contracts)
        $activeContracts = CandidateContract::where('vessel_id', $vesselId)
            ->whereNull('end_date')
            ->get();

        $filledRanks = $activeContracts->pluck('rank_code')->toArray();

        // Build rank gap analysis
        $rankGaps = [];
        foreach ($rankReqs as $req) {
            $rankCode = $req['rank_code'];
            $isFilled = in_array($rankCode, $filledRanks);
            $rankGaps[] = [
                'rank' => str_replace('_', ' ', ucwords($rankCode, '_')),
                'rank_code' => $rankCode,
                'status' => $isFilled ? 'filled' : 'gap',
                'note' => $isFilled
                    ? 'Position currently filled'
                    : 'No active crew member assigned',
            ];
        }

        // Count available candidates for gap ranks
        $gapRankCodes = collect($rankGaps)
            ->where('status', 'gap')
            ->pluck('rank_code')
            ->toArray();

        $availableCandidates = 0;
        if (!empty($gapRankCodes)) {
            $availableCandidates = PoolCandidate::whereIn('status', ['in_pool', 'assessed'])
                ->where('seafarer', true)
                ->whereNotNull('email_verified_at')
                ->where('is_demo', false)
                ->whereHas('contracts', function ($q) use ($vessel) {
                    if ($vessel->type) {
                        $q->where('vessel_type', $vessel->type);
                    }
                })
                ->count();
        }

        // Certificate conflicts (candidates with expiring certificates)
        $certConflicts = 0;

        // Compatibility notes
        $notes = [];
        $gapCount = collect($rankGaps)->where('status', 'gap')->count();
        if ($gapCount > 0) {
            $notes[] = "{$gapCount} rank(s) need crew assignment";
        }
        if ($availableCandidates > 0) {
            $notes[] = "{$availableCandidates} pre-screened candidates available in talent pool";
        }
        if ($gapCount === 0) {
            $notes[] = "All required positions are currently filled";
        }

        return response()->json([
            'success' => true,
            'data' => [
                'vessel_id' => $vessel->id,
                'vessel_name' => $vessel->name,
                'rank_gaps' => $rankGaps,
                'available_candidates' => $availableCandidates,
                'certificate_conflicts' => $certConflicts,
                'compatibility_notes' => $notes,
            ],
        ]);
    }
}
