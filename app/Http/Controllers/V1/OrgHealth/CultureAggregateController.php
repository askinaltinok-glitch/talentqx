<?php

namespace App\Http\Controllers\V1\OrgHealth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CultureAggregateController extends Controller
{
    private const MIN_N = 5;

    private const SCORE_COLS = [
        'clan_current', 'clan_preferred',
        'adhocracy_current', 'adhocracy_preferred',
        'market_current', 'market_preferred',
        'hierarchy_current', 'hierarchy_preferred',
    ];

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

        // Subquery: latest culture profile per employee
        $subSql = "SELECT cp.* FROM org_culture_profiles cp
            INNER JOIN (
                SELECT employee_id, MAX(computed_at) as max_computed
                FROM org_culture_profiles
                WHERE tenant_id = ?
                GROUP BY employee_id
            ) latest ON cp.employee_id = latest.employee_id AND cp.computed_at = latest.max_computed
            WHERE cp.tenant_id = ?";
        $bindings = [$tenantId, $tenantId];

        // Overall averages
        $avgCols = implode(', ', array_map(
            fn($c) => "ROUND(AVG($c), 2) as avg_$c",
            self::SCORE_COLS
        ));
        $overallRow = DB::selectOne(
            "SELECT COUNT(*) as cnt, $avgCols FROM ($subSql) as lp",
            $bindings
        );

        $overall = ['n' => (int) ($overallRow->cnt ?? 0), 'current' => [], 'preferred' => []];
        foreach (['clan', 'adhocracy', 'market', 'hierarchy'] as $type) {
            $overall['current'][$type] = (float) ($overallRow->{"avg_{$type}_current"} ?? 0);
            $overall['preferred'][$type] = (float) ($overallRow->{"avg_{$type}_preferred"} ?? 0);
        }

        // Per-department averages
        $deptAvgCols = implode(', ', array_map(
            fn($c) => "ROUND(AVG(lp.$c), 2) as avg_$c",
            self::SCORE_COLS
        ));
        $deptRows = DB::select(
            "SELECT e.department_code, COUNT(*) as n, $deptAvgCols
             FROM ($subSql) as lp
             INNER JOIN org_employees e ON lp.employee_id = e.id
             WHERE e.department_code IS NOT NULL
             GROUP BY e.department_code
             HAVING COUNT(*) >= ?
             ORDER BY e.department_code",
            [...$bindings, self::MIN_N]
        );

        $departments = collect($deptRows)->map(function ($row) {
            $dept = [
                'department_code' => $row->department_code,
                'n' => (int) $row->n,
                'current' => [],
                'preferred' => [],
            ];
            foreach (['clan', 'adhocracy', 'market', 'hierarchy'] as $type) {
                $dept['current'][$type] = (float) ($row->{"avg_{$type}_current"} ?? 0);
                $dept['preferred'][$type] = (float) ($row->{"avg_{$type}_preferred"} ?? 0);
            }
            return $dept;
        });

        return response()->json([
            'tenant_id' => $tenantId,
            'overall' => $overall,
            'departments' => $departments,
        ]);
    }
}
