<?php

namespace App\Http\Controllers\Api\OctopusAdmin;

use App\Http\Controllers\Controller;
use App\Models\CountryCertificateRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryCertificateRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CountryCertificateRule::orderBy('country_code')->orderBy('certificate_type');

        if ($request->filled('country_code')) {
            $query->where('country_code', strtoupper($request->input('country_code')));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_code' => 'required|string|size:2',
            'certificate_type' => 'required|string|max:32',
            'validity_months' => 'required|integer|min:1|max:600',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['country_code'] = strtoupper($validated['country_code']);

        $rule = CountryCertificateRule::updateOrCreate(
            [
                'country_code' => $validated['country_code'],
                'certificate_type' => $validated['certificate_type'],
            ],
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $rule,
        ], $rule->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $rule = CountryCertificateRule::findOrFail($id);
        $rule->delete();

        return response()->json(['success' => true]);
    }
}
