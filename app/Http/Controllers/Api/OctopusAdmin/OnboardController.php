<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\CrmCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardController extends Controller
{
    /**
     * POST /v1/octopus/admin/onboard
     *
     * Create a CrmCompany from the onboarding wizard intelligence.
     * Dedup by domain if website provided.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:128'],
            'company_type' => ['required', 'string', 'in:ship_manager,ship_owner,manning_agent,offshore_operator,tanker_operator,charterer,agency,training_center,other'],
            'website' => ['nullable', 'url', 'max:500'],
            'operations' => ['required', 'array', 'min:1'],
            'operations.*' => ['string', 'in:sea,river'],
            'vessel_types' => ['required', 'array', 'min:1'],
            'vessel_types.*' => ['string', 'max:64'],
            'crew_type' => ['required', 'string', 'in:officers,ratings,both'],
            'intended_action' => ['required', 'string', 'in:upload_excel,create_crew_request,explore'],
        ]);

        $domain = CrmCompany::extractDomain($data['website'] ?? null);

        // Dedup by domain
        if ($domain) {
            $existing = CrmCompany::findByDomain($domain);
            if ($existing) {
                return response()->json([
                    'success' => true,
                    'data' => $existing,
                    'existing' => true,
                    'message' => 'Company already exists with this domain.',
                ]);
            }
        }

        $company = CrmCompany::create([
            'name' => $data['name'],
            'country_code' => strtoupper($data['country_code']),
            'city' => $data['city'] ?? null,
            'company_type' => $data['company_type'],
            'website' => $data['website'] ?? null,
            'domain' => $domain,
            'industry_code' => CrmCompany::INDUSTRY_MARITIME,
            'operations' => $data['operations'],
            'onboarding_data' => [
                'vessel_types' => $data['vessel_types'],
                'crew_type' => $data['crew_type'],
                'intended_action' => $data['intended_action'],
            ],
            'status' => CrmCompany::STATUS_NEW,
        ]);

        return response()->json([
            'success' => true,
            'data' => $company,
            'existing' => false,
        ], 201);
    }
}
