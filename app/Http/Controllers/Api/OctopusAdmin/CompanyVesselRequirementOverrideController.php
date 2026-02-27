<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyVesselRequirementOverride;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyVesselRequirementOverrideController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CompanyVesselRequirementOverride::orderBy('vessel_type_key');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'uuid', Rule::exists('companies', 'id')->where('platform', Company::PLATFORM_OCTOPUS)],
            'vessel_type_key' => 'required|string|max:50',
            'overrides_json' => 'required|array',
        ]);

        $override = CompanyVesselRequirementOverride::updateOrCreate(
            [
                'company_id' => $validated['company_id'],
                'vessel_type_key' => $validated['vessel_type_key'],
            ],
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $override,
        ], $override->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $override = CompanyVesselRequirementOverride::findOrFail($id);
        $override->delete();

        return response()->json(['success' => true]);
    }
}
