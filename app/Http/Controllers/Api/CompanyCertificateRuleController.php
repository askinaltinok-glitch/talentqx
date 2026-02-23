<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyCertificateRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyCertificateRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $rules = CompanyCertificateRule::where('company_id', $companyId)
            ->orderBy('certificate_type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'certificate_type' => 'required|string|max:32',
            'validity_months' => 'required|integer|min:1|max:600',
            'notes' => 'nullable|string|max:500',
        ]);

        $rule = CompanyCertificateRule::updateOrCreate(
            [
                'company_id' => $companyId,
                'certificate_type' => $validated['certificate_type'],
            ],
            array_merge($validated, ['company_id' => $companyId])
        );

        return response()->json([
            'success' => true,
            'data' => $rule,
        ], $rule->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $rule = CompanyCertificateRule::where('company_id', $companyId)
            ->findOrFail($id);

        $rule->delete();

        return response()->json(['success' => true]);
    }
}
