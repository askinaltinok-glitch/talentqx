<?php

namespace App\Http\Controllers\V1\OrgHealth;

use App\Http\Controllers\Controller;
use App\Models\OrgWorkstyleProfile;
use Illuminate\Http\Request;

class WorkstyleProfileController extends Controller
{
    public function latest(Request $request, string $employeeId)
    {
        $tenantId = $request->user()->company_id;

        $profile = OrgWorkstyleProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->orderByDesc('computed_at')
            ->firstOrFail();

        return response()->json([
            'employee_id' => $employeeId,
            'assessment_id' => $profile->assessment_id,
            'planning_score' => $profile->planning_score,
            'social_score' => $profile->social_score,
            'cooperation_score' => $profile->cooperation_score,
            'stability_score' => $profile->stability_score,
            'adaptability_score' => $profile->adaptability_score,
            'computed_at' => $profile->computed_at,
        ]);
    }

    public function history(Request $request, string $employeeId)
    {
        $tenantId = $request->user()->company_id;

        $profiles = OrgWorkstyleProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->orderByDesc('computed_at')
            ->get()
            ->map(fn($p) => [
                'assessment_id' => $p->assessment_id,
                'planning_score' => $p->planning_score,
                'social_score' => $p->social_score,
                'cooperation_score' => $p->cooperation_score,
                'stability_score' => $p->stability_score,
                'adaptability_score' => $p->adaptability_score,
                'computed_at' => $p->computed_at,
            ]);

        return response()->json(['employee_id' => $employeeId, 'profiles' => $profiles]);
    }
}
