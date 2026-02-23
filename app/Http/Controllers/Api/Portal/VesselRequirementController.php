<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\CompanyVesselRequirementOverride;
use App\Models\VesselRequirementTemplate;
use App\Services\Fleet\VesselRequirementProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VesselRequirementController extends Controller
{
    public function __construct(
        private VesselRequirementProfileService $profileService
    ) {}

    /**
     * GET /v1/portal/vessel-requirements
     * All 6 templates with company overrides merged.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $templates = VesselRequirementTemplate::where('is_active', true)
            ->orderBy('vessel_type_key')
            ->get();

        $overrides = CompanyVesselRequirementOverride::where('company_id', $companyId)
            ->get()
            ->keyBy('vessel_type_key');

        $result = $templates->map(function (VesselRequirementTemplate $tpl) use ($overrides, $companyId) {
            $override = $overrides->get($tpl->vessel_type_key);
            $merged = $tpl->profile_json;

            if ($override && is_array($override->overrides_json)) {
                $merged = $this->profileService->mergeProfile($merged, $override->overrides_json);
            }

            return [
                'vessel_type_key' => $tpl->vessel_type_key,
                'label' => $tpl->label,
                'default_profile' => $tpl->profile_json,
                'merged_profile' => $merged,
                'has_override' => $override !== null,
                'override_id' => $override?->id,
                'overrides_json' => $override?->overrides_json,
            ];
        });

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * GET /v1/portal/vessel-requirements/{typeKey}
     * Single template + override detail.
     */
    public function show(Request $request, string $typeKey): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $template = VesselRequirementTemplate::where('vessel_type_key', $typeKey)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found.',
            ], 404);
        }

        $override = CompanyVesselRequirementOverride::forCompanyAndType($companyId, $typeKey);
        $merged = $template->profile_json;

        if ($override && is_array($override->overrides_json)) {
            $merged = $this->profileService->mergeProfile($merged, $override->overrides_json);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'vessel_type_key' => $template->vessel_type_key,
                'label' => $template->label,
                'default_profile' => $template->profile_json,
                'merged_profile' => $merged,
                'has_override' => $override !== null,
                'override_id' => $override?->id,
                'overrides_json' => $override?->overrides_json,
            ],
        ]);
    }

    /**
     * PUT /v1/portal/vessel-requirements/{typeKey}
     * Create or update company override for a vessel type.
     */
    public function update(Request $request, string $typeKey): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $template = VesselRequirementTemplate::where('vessel_type_key', $typeKey)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found.',
            ], 404);
        }

        $data = $request->validate([
            'overrides' => 'required|array',
            'overrides.required_certificates' => 'sometimes|array',
            'overrides.required_certificates.*.certificate_type' => 'required_with:overrides.required_certificates|string',
            'overrides.required_certificates.*.min_remaining_months' => 'sometimes|integer|min:0',
            'overrides.required_certificates.*.mandatory' => 'sometimes|boolean',
            'overrides.required_certificates.*.hard_block' => 'sometimes|boolean',
            'overrides.required_certificates.*.block_reason_key' => 'sometimes|nullable|string|max:100',
            'overrides.experience' => 'sometimes|array',
            'overrides.experience.vessel_type_min_months' => 'sometimes|integer|min:0',
            'overrides.behavior_thresholds' => 'sometimes|array',
            'overrides.weights' => 'sometimes|array',
            'overrides.weights.cert_fit' => 'sometimes|numeric|min:0|max:1',
            'overrides.weights.experience_fit' => 'sometimes|numeric|min:0|max:1',
            'overrides.weights.behavior_fit' => 'sometimes|numeric|min:0|max:1',
            'overrides.weights.availability_fit' => 'sometimes|numeric|min:0|max:1',
        ]);

        // Validate weights sum to ~1.0 if provided
        if (isset($data['overrides']['weights'])) {
            $weights = $data['overrides']['weights'];
            $sum = array_sum($weights);
            if (abs($sum - 1.0) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Weights must sum to 1.0 (current sum: ' . round($sum, 2) . ').',
                ], 422);
            }
        }

        $override = CompanyVesselRequirementOverride::updateOrCreate(
            ['company_id' => $companyId, 'vessel_type_key' => $typeKey],
            ['overrides_json' => $data['overrides']]
        );

        $merged = $this->profileService->mergeProfile(
            $template->profile_json,
            $override->overrides_json
        );

        return response()->json([
            'success' => true,
            'data' => [
                'vessel_type_key' => $typeKey,
                'label' => $template->label,
                'merged_profile' => $merged,
                'has_override' => true,
                'override_id' => $override->id,
                'overrides_json' => $override->overrides_json,
            ],
        ]);
    }

    /**
     * DELETE /v1/portal/vessel-requirements/{typeKey}
     * Reset to default (delete company override).
     */
    public function destroy(Request $request, string $typeKey): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $deleted = CompanyVesselRequirementOverride::where('company_id', $companyId)
            ->where('vessel_type_key', $typeKey)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted ? 'Override removed. Template reset to default.' : 'No override found.',
        ]);
    }
}
