<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingFinalizeController extends Controller
{
    /**
     * GET /v1/portal/onboarding — current company profile for onboarding form.
     */
    public function show(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'No company associated'], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                'legal_name' => $company->legal_name,
                'country' => $company->country,
                'timezone' => $company->timezone,
                'fleet_size' => $company->fleet_size,
                'management_type' => $company->management_type,
                'onboarding_completed' => (bool) $company->onboarding_completed,
            ],
        ]);
    }

    /**
     * PUT /v1/portal/onboarding — save/complete onboarding profile.
     */
    public function update(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'No company associated'], 422);
        }

        $data = $request->validate([
            'legal_name' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:60',
            'fleet_size' => 'nullable|string|max:30',
            'management_type' => 'nullable|string|in:ship_manager,ship_owner,manning_agent,offshore_operator,tanker_operator,other',
        ]);

        $company->update($data);
        $company->update(['onboarding_completed' => true]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                'onboarding_completed' => true,
            ],
        ]);
    }
}
