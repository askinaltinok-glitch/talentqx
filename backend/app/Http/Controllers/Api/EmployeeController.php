<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\KVKK\EmployeeDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Employee::with(['latestAssessment.result'])
            ->where('company_id', $request->user()->company_id);

        if ($request->has('role')) {
            $query->where('current_role', $request->role);
        }

        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        if ($request->has('branch')) {
            $query->where('branch', $request->branch);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('risk_level')) {
            $query->whereHas('latestAssessment.result', function ($q) use ($request) {
                $q->where('risk_level', $request->risk_level);
            });
        }

        if ($request->boolean('high_risk_only')) {
            $query->whereHas('latestAssessment.result', function ($q) {
                $q->whereIn('risk_level', ['high', 'critical']);
            });
        }

        $employees = $query->orderBy('last_name')->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_code' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:100',
            'current_role' => 'required|string|max:100',
            'branch' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'manager_name' => 'nullable|string|max:255',
        ]);

        $employee = Employee::create([
            ...$validated,
            'company_id' => $request->user()->company_id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $employee,
            'message' => 'Calisan basariyla eklendi.',
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $employee = Employee::with([
            'assessmentSessions.template',
            'assessmentSessions.result',
        ])
            ->where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $employee,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $employee = Employee::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'employee_code' => 'nullable|string|max:50',
            'first_name' => 'string|max:100',
            'last_name' => 'string|max:100',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:100',
            'current_role' => 'string|max:100',
            'branch' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'manager_name' => 'nullable|string|max:255',
            'status' => 'in:active,inactive,terminated',
        ]);

        $employee->update($validated);

        return response()->json([
            'success' => true,
            'data' => $employee->fresh(),
            'message' => 'Calisan bilgileri guncellendi.',
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $employee = Employee::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Calisan silindi.',
        ]);
    }

    public function bulkImport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employees' => 'required|array|min:1|max:500',
            'employees.*.first_name' => 'required|string|max:100',
            'employees.*.last_name' => 'required|string|max:100',
            'employees.*.current_role' => 'required|string|max:100',
            'employees.*.employee_code' => 'nullable|string|max:50',
            'employees.*.email' => 'nullable|email',
            'employees.*.phone' => 'nullable|string|max:20',
            'employees.*.department' => 'nullable|string|max:100',
            'employees.*.branch' => 'nullable|string|max:100',
        ]);

        $imported = 0;
        $errors = [];

        foreach ($validated['employees'] as $index => $employeeData) {
            try {
                Employee::create([
                    ...$employeeData,
                    'company_id' => $request->user()->company_id,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Satir {$index}: {$e->getMessage()}";
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'imported' => $imported,
                'errors' => $errors,
            ],
            'message' => "{$imported} calisan eklendi.",
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $totalEmployees = Employee::where('company_id', $companyId)->count();
        $activeEmployees = Employee::where('company_id', $companyId)->where('status', 'active')->count();

        $byRole = Employee::where('company_id', $companyId)
            ->selectRaw('current_role, count(*) as count')
            ->groupBy('current_role')
            ->pluck('count', 'current_role');

        $byDepartment = Employee::where('company_id', $companyId)
            ->whereNotNull('department')
            ->selectRaw('department, count(*) as count')
            ->groupBy('department')
            ->pluck('count', 'department');

        $assessedCount = Employee::where('company_id', $companyId)
            ->whereHas('latestAssessment', fn($q) => $q->where('status', 'completed'))
            ->count();

        $highRiskCount = Employee::where('company_id', $companyId)
            ->whereHas('latestAssessment.result', fn($q) => $q->whereIn('risk_level', ['high', 'critical']))
            ->count();

        // Cheating risk stats
        $highCheatingRiskCount = Employee::where('company_id', $companyId)
            ->whereHas('latestAssessment.result', fn($q) => $q->where('cheating_level', 'high'))
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_employees' => $totalEmployees,
                'active_employees' => $activeEmployees,
                'assessed_count' => $assessedCount,
                'assessment_rate' => $activeEmployees > 0 ? round($assessedCount / $activeEmployees * 100, 1) : 0,
                'high_risk_count' => $highRiskCount,
                'high_cheating_risk_count' => $highCheatingRiskCount,
                'by_role' => $byRole,
                'by_department' => $byDepartment,
            ],
        ]);
    }

    /**
     * KVKK - Erase employee data (Right to be Forgotten)
     */
    public function erase(Request $request, string $id): JsonResponse
    {
        $employee = Employee::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        if ($employee->isErased()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_ERASED',
                    'message' => 'Bu calisanin verileri zaten silinmis.',
                ],
            ], 400);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $service = new EmployeeDataService();

        try {
            $result = $service->eraseEmployeeData(
                $employee,
                $validated['reason'],
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Calisan verileri KVKK uyumlu olarak silindi.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ERASURE_FAILED',
                    'message' => 'Veri silme islemi basarisiz oldu: ' . $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * KVKK - Export employee data (Data Portability)
     */
    public function export(Request $request, string $id): JsonResponse
    {
        $employee = Employee::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        if ($employee->isErased()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DATA_ERASED',
                    'message' => 'Bu calisanin verileri silinmis durumda.',
                ],
            ], 410);
        }

        $service = new EmployeeDataService();
        $data = $service->exportEmployeeData($employee);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * KVKK - Get retention statistics
     */
    public function retentionStats(Request $request): JsonResponse
    {
        $service = new EmployeeDataService();
        $stats = $service->getRetentionStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Update employee retention period
     */
    public function updateRetention(Request $request, string $id): JsonResponse
    {
        $employee = Employee::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'retention_days' => 'required|integer|min:30|max:730', // 30 days to 2 years
        ]);

        $employee->update(['retention_days' => $validated['retention_days']]);

        return response()->json([
            'success' => true,
            'data' => $employee->fresh(),
            'message' => 'Veri saklama suresi guncellendi.',
        ]);
    }
}
