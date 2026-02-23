<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyVessel;
use App\Models\Vessel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyVesselController extends Controller
{
    /**
     * List vessels assigned to the current tenant.
     * GET /api/v1/company/vessels
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = app()->bound('current_tenant_id') ? app('current_tenant_id') : $request->user()->company_id;

        $vessels = CompanyVessel::with('vessel')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByDesc('assigned_at')
            ->get()
            ->map(fn(CompanyVessel $cv) => [
                'id' => $cv->id,
                'vessel_id' => $cv->vessel_id,
                'vessel' => $cv->vessel ? [
                    'id' => $cv->vessel->id,
                    'name' => $cv->vessel->name,
                    'imo' => $cv->vessel->imo,
                    'type' => $cv->vessel->type,
                    'flag' => $cv->vessel->flag,
                ] : null,
                'role' => $cv->role,
                'assigned_at' => $cv->assigned_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $vessels,
        ]);
    }

    /**
     * Assign a vessel to the current tenant's company.
     * POST /api/v1/company/vessels
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vessel_id' => ['required', 'uuid', 'exists:vessels,id'],
            'role' => ['nullable', 'string', 'in:owner,operator,manager'],
        ]);

        $companyId = app()->bound('current_tenant_id') ? app('current_tenant_id') : $request->user()->company_id;

        // Check for existing (possibly soft-deleted) assignment
        $existing = CompanyVessel::withTrashed()
            ->where('company_id', $companyId)
            ->where('vessel_id', $data['vessel_id'])
            ->first();

        if ($existing && !$existing->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Vessel is already assigned to this company.',
            ], 409);
        }

        if ($existing && $existing->trashed()) {
            $existing->restore();
            $existing->update([
                'role' => $data['role'] ?? 'operator',
                'is_active' => true,
                'assigned_at' => now(),
            ]);
            $cv = $existing;
        } else {
            $cv = CompanyVessel::create([
                'company_id' => $companyId,
                'vessel_id' => $data['vessel_id'],
                'role' => $data['role'] ?? 'operator',
                'is_active' => true,
                'assigned_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cv->id,
                'vessel_id' => $cv->vessel_id,
                'role' => $cv->role,
            ],
        ], 201);
    }

    /**
     * Remove vessel assignment (soft delete).
     * DELETE /api/v1/company/vessels/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $companyId = app()->bound('current_tenant_id') ? app('current_tenant_id') : $request->user()->company_id;

        $cv = CompanyVessel::where('company_id', $companyId)->find($id);

        if (!$cv) {
            return response()->json([
                'success' => false,
                'message' => 'Vessel assignment not found.',
            ], 404);
        }

        $cv->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vessel assignment removed.',
        ]);
    }
}
