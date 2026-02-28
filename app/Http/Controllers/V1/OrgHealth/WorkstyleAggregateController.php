<?php

namespace App\Http\Controllers\V1\OrgHealth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkstyleAggregateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Only hr_manager, admin, or platform admin can access aggregate data
        if (!$user->isAdmin() && !$user->isPlatformAdmin() && $user->role?->name !== 'hr_manager') {
            return response()->json([
                'error' => 'forbidden',
                'message' => 'HR manager access required.',
            ], 403);
        }

        $tenantId = $user->company_id;

        $dimensions = ['planning_score', 'social_score', 'cooperation_score', 'stability_score', 'adaptability_score'];

        // Build the "latest profile per employee" subquery SQL
        $subSql = "SELECT p1.* FROM org_workstyle_profiles p1
            INNER JOIN (
                SELECT employee_id, MAX(computed_at) as max_computed
                FROM org_workstyle_profiles
                WHERE tenant_id = ?
                GROUP BY employee_id
            ) p2 ON p1.employee_id = p2.employee_id AND p1.computed_at = p2.max_computed
            WHERE p1.tenant_id = ?";
        $subBindings = [$tenantId, $tenantId];

        // Overall averages
        $avgCols = implode(', ', array_map(fn($d) => "ROUND(AVG($d), 2) as avg_$d", $dimensions));
        $overallRow = DB::selectOne(
            "SELECT COUNT(*) as cnt, $avgCols FROM ($subSql) as lp",
            $subBindings
        );

        $overall = [];
        foreach ($dimensions as $d) {
            $key = str_replace('_score', '', $d);
            $overall[$key] = (float)($overallRow->{"avg_$d"} ?? 0);
        }

        // Per-department averages
        $deptAvgCols = implode(', ', array_map(fn($d) => "ROUND(AVG(lp.$d), 2) as avg_$d", $dimensions));
        $deptRows = DB::select(
            "SELECT e.department_code, COUNT(*) as employee_count, $deptAvgCols
             FROM ($subSql) as lp
             INNER JOIN org_employees e ON lp.employee_id = e.id
             WHERE e.department_code IS NOT NULL
             GROUP BY e.department_code
             ORDER BY e.department_code",
            $subBindings
        );

        $departments = collect($deptRows)->map(function ($row) use ($dimensions) {
            $avgs = [];
            foreach ($dimensions as $d) {
                $key = str_replace('_score', '', $d);
                $avgs[$key] = (float)($row->{"avg_$d"} ?? 0);
            }
            return [
                'department_code' => $row->department_code,
                'employee_count' => (int)$row->employee_count,
                'averages' => $avgs,
            ];
        });

        return response()->json([
            'tenant_id' => $tenantId,
            'total_profiles' => (int)($overallRow->cnt ?? 0),
            'overall' => $overall,
            'departments' => $departments,
        ]);
    }
}
