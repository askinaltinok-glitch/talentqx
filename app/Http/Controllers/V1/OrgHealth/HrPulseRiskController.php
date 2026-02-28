<?php

namespace App\Http\Controllers\V1\OrgHealth;

use App\Http\Controllers\Controller;
use App\Models\OrgPulseProfile;
use App\Models\OrgPulseRiskSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrPulseRiskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Only hr_manager, admin, or platform admin
        if (!$user->isAdmin() && !$user->isPlatformAdmin() && $user->role?->name !== 'hr_manager') {
            return response()->json([
                'error' => 'forbidden',
                'message' => 'HR manager access required.',
            ], 403);
        }

        $tenantId = $user->company_id;

        // Get latest risk snapshot per employee via subquery
        $latestSnapshots = OrgPulseRiskSnapshot::query()
            ->select('org_pulse_risk_snapshots.*')
            ->joinSub(
                OrgPulseRiskSnapshot::query()
                    ->select('employee_id', DB::raw('MAX(computed_at) as max_computed'))
                    ->where('tenant_id', $tenantId)
                    ->groupBy('employee_id'),
                'latest',
                function ($join) {
                    $join->on('org_pulse_risk_snapshots.employee_id', '=', 'latest.employee_id')
                        ->on('org_pulse_risk_snapshots.computed_at', '=', 'latest.max_computed');
                }
            )
            ->where('org_pulse_risk_snapshots.tenant_id', $tenantId)
            ->orderByDesc('org_pulse_risk_snapshots.risk_score')
            ->get();

        // Enrich with employee name/department
        $employeeIds = $latestSnapshots->pluck('employee_id')->unique();
        $employees = DB::table('org_employees')
            ->whereIn('id', $employeeIds)
            ->get()
            ->keyBy('id');

        $results = $latestSnapshots->map(function ($snap) use ($employees) {
            $emp = $employees[$snap->employee_id] ?? null;
            return [
                'employee_id' => $snap->employee_id,
                'employee_name' => $emp->full_name ?? 'Unknown',
                'department_code' => $emp->department_code ?? null,
                'risk_score' => (int) $snap->risk_score,
                'risk_level' => $snap->risk_level,
                'drivers' => $snap->drivers ?? [],
                'computed_at' => $snap->computed_at->toIso8601String(),
            ];
        });

        return response()->json([
            'tenant_id' => $tenantId,
            'employees' => $results->values(),
        ]);
    }

    public function show(Request $request, string $employeeId)
    {
        $user = $request->user();

        // Only hr_manager, admin, or platform admin
        if (!$user->isAdmin() && !$user->isPlatformAdmin() && $user->role?->name !== 'hr_manager') {
            return response()->json([
                'error' => 'forbidden',
                'message' => 'HR manager access required.',
            ], 403);
        }

        $tenantId = $user->company_id;

        // Latest risk snapshot
        $riskSnapshot = OrgPulseRiskSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->orderByDesc('computed_at')
            ->first();

        if (!$riskSnapshot) {
            return response()->json(['message' => 'No risk data found for this employee.'], 404);
        }

        // Latest pulse profile
        $latestProfile = OrgPulseProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->orderByDesc('computed_at')
            ->first();

        // Pulse history (last 12)
        $history = OrgPulseProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->orderByDesc('computed_at')
            ->limit(12)
            ->get();

        // Employee info
        $employee = DB::table('org_employees')->where('id', $employeeId)->first();

        return response()->json([
            'employee_id' => $employeeId,
            'employee_name' => $employee->full_name ?? 'Unknown',
            'department_code' => $employee->department_code ?? null,
            'risk' => [
                'risk_score' => (int) $riskSnapshot->risk_score,
                'risk_level' => $riskSnapshot->risk_level,
                'drivers' => $riskSnapshot->drivers ?? [],
                'suggestions' => $riskSnapshot->suggestions ?? [],
                'computed_at' => $riskSnapshot->computed_at->toIso8601String(),
            ],
            'latest_profile' => $latestProfile ? [
                'engagement_score' => (float) $latestProfile->engagement_score,
                'wellbeing_score' => (float) $latestProfile->wellbeing_score,
                'alignment_score' => (float) $latestProfile->alignment_score,
                'growth_score' => (float) $latestProfile->growth_score,
                'retention_intent_score' => (float) $latestProfile->retention_intent_score,
                'overall_score' => (float) $latestProfile->overall_score,
                'burnout_proxy' => (float) $latestProfile->burnout_proxy,
                'computed_at' => $latestProfile->computed_at->toIso8601String(),
            ] : null,
            'history' => $history->map(fn($p) => [
                'overall_score' => (float) $p->overall_score,
                'engagement_score' => (float) $p->engagement_score,
                'wellbeing_score' => (float) $p->wellbeing_score,
                'alignment_score' => (float) $p->alignment_score,
                'growth_score' => (float) $p->growth_score,
                'retention_intent_score' => (float) $p->retention_intent_score,
                'burnout_proxy' => (float) $p->burnout_proxy,
                'computed_at' => $p->computed_at->toIso8601String(),
            ])->values(),
        ]);
    }
}
