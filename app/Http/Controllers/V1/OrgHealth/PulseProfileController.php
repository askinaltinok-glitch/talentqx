<?php

namespace App\Http\Controllers\V1\OrgHealth;

use App\Http\Controllers\Controller;
use App\Models\OrgPulseProfile;
use Illuminate\Http\Request;

class PulseProfileController extends Controller
{
    public function latest(Request $request, string $employeeId)
    {
        $tenantId = $request->user()->company_id;

        $profile = OrgPulseProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->orderByDesc('computed_at')
            ->first();

        if (!$profile) {
            return response()->json(['message' => 'No pulse profile found.'], 404);
        }

        return response()->json([
            'employee_id' => $profile->employee_id,
            'assessment_id' => $profile->assessment_id,
            'engagement_score' => (float) $profile->engagement_score,
            'wellbeing_score' => (float) $profile->wellbeing_score,
            'alignment_score' => (float) $profile->alignment_score,
            'growth_score' => (float) $profile->growth_score,
            'retention_intent_score' => (float) $profile->retention_intent_score,
            'overall_score' => (float) $profile->overall_score,
            'burnout_proxy' => (float) $profile->burnout_proxy,
            'computed_at' => $profile->computed_at->toIso8601String(),
        ]);
    }

    public function history(Request $request, string $employeeId)
    {
        $tenantId = $request->user()->company_id;

        $profiles = OrgPulseProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->orderByDesc('computed_at')
            ->limit(12)
            ->get();

        return response()->json([
            'employee_id' => $employeeId,
            'profiles' => $profiles->map(fn($p) => [
                'assessment_id' => $p->assessment_id,
                'engagement_score' => (float) $p->engagement_score,
                'wellbeing_score' => (float) $p->wellbeing_score,
                'alignment_score' => (float) $p->alignment_score,
                'growth_score' => (float) $p->growth_score,
                'retention_intent_score' => (float) $p->retention_intent_score,
                'overall_score' => (float) $p->overall_score,
                'burnout_proxy' => (float) $p->burnout_proxy,
                'computed_at' => $p->computed_at->toIso8601String(),
            ])->values(),
        ]);
    }
}
