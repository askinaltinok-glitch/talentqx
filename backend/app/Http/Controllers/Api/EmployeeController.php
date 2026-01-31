<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeImportBatch;
use App\Services\KVKK\EmployeeDataService;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'email' => 'nullable|email|max:190',
            'phone' => 'nullable|string|max:40',
            'department' => 'nullable|string|max:100',
            'current_role' => 'required|string|max:120',
            'branch' => 'nullable|string|max:190',
            'hire_date' => 'nullable|date',
            'manager_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        // Email or phone required
        if (empty($validated['email']) && empty($validated['phone'])) {
            return response()->json([
                'success' => false,
                'message' => 'email_or_phone_required',
            ], 422);
        }

        // Check for duplicates
        $companyId = $request->user()->company_id;
        $duplicateQuery = Employee::where('company_id', $companyId);

        if (!empty($validated['email']) && !empty($validated['phone'])) {
            $duplicateQuery->where(function ($q) use ($validated) {
                $q->where('email', $validated['email'])
                  ->orWhere('phone', $validated['phone']);
            });
        } elseif (!empty($validated['email'])) {
            $duplicateQuery->where('email', $validated['email']);
        } else {
            $duplicateQuery->where('phone', $validated['phone']);
        }

        if ($duplicateQuery->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'duplicate_employee',
            ], 409);
        }

        $employee = Employee::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'current_role' => $validated['current_role'],
            'branch' => $validated['branch'] ?? null,
            'department' => $validated['department'] ?? null,
            'employee_code' => $validated['employee_code'] ?? null,
            'hire_date' => $validated['hire_date'] ?? null,
            'manager_name' => $validated['manager_name'] ?? null,
            'metadata' => array_filter([
                'notes' => $validated['notes'] ?? null,
            ]),
            'company_id' => $companyId,
            'status' => 'active',
        ]);

        // Audit log
        Audit::log('employee_created', 'employee', $employee->id, [
            'name' => $employee->first_name . ' ' . $employee->last_name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'role' => $employee->current_role,
        ], $request);

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

        // Store data for audit before delete
        $auditData = [
            'name' => $employee->first_name . ' ' . $employee->last_name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'role' => $employee->current_role,
        ];

        $employee->delete();

        // Audit log
        Audit::log('employee_deleted', 'employee', $id, $auditData, $request);

        return response()->json([
            'success' => true,
            'message' => 'Calisan silindi.',
        ]);
    }

    public function bulkImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:2048',
        ]);

        // Manual extension check (fileinfo extension may not be available)
        $extension = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'txt'])) {
            return response()->json([
                'success' => false,
                'message' => 'Sadece CSV dosyası yükleyebilirsiniz.',
            ], 422);
        }

        $file = $request->file('file');
        $companyId = $request->user()->company_id;
        $userId = $request->user()->id;

        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        $handle = fopen($file->getPathname(), 'r');
        if ($handle === false) {
            return response()->json([
                'success' => false,
                'error' => ['message' => 'Dosya okunamadi.'],
            ], 400);
        }

        // Read header row
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return response()->json([
                'success' => false,
                'error' => ['message' => 'CSV baslik satiri okunamadi.'],
            ], 400);
        }

        // Normalize header names
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        // Map CSV columns to database fields
        $columnMap = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone' => 'phone',
            'email' => 'email',
            'role' => 'current_role',
            'current_role' => 'current_role',
            'store' => 'branch',
            'branch' => 'branch',
            'department' => 'department',
            'employee_code' => 'employee_code',
        ];

        // Create import batch record
        $batch = EmployeeImportBatch::create([
            'company_id' => $companyId,
            'created_by_user_id' => $userId,
            'filename' => $file->getClientOriginalName(),
            'imported_count' => 0,
            'skipped_count' => 0,
        ]);

        $rowNumber = 1; // Header is row 0
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Map row to associative array
            $data = [];
            foreach ($header as $index => $columnName) {
                if (isset($columnMap[$columnName]) && isset($row[$index])) {
                    $data[$columnMap[$columnName]] = trim($row[$index]);
                }
            }

            // Validate required fields
            if (empty($data['first_name']) || empty($data['last_name'])) {
                $errors[] = ['row' => $rowNumber, 'reason' => 'Ad ve soyad zorunludur'];
                $skippedCount++;
                continue;
            }

            if (empty($data['current_role'])) {
                $errors[] = ['row' => $rowNumber, 'reason' => 'Rol zorunludur'];
                $skippedCount++;
                continue;
            }

            if (empty($data['email']) && empty($data['phone'])) {
                $errors[] = ['row' => $rowNumber, 'reason' => 'Email veya telefon zorunludur'];
                $skippedCount++;
                continue;
            }

            // Check for duplicates
            $existsQuery = Employee::where('company_id', $companyId);
            if (!empty($data['email'])) {
                $existsQuery->where('email', $data['email']);
            } elseif (!empty($data['phone'])) {
                $existsQuery->where('phone', $data['phone']);
            }

            if ($existsQuery->exists()) {
                $errors[] = ['row' => $rowNumber, 'reason' => 'Bu email/telefon zaten kayitli'];
                $skippedCount++;
                continue;
            }

            // Create employee with batch tracking
            try {
                Employee::create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'current_role' => $data['current_role'],
                    'branch' => $data['branch'] ?? null,
                    'department' => $data['department'] ?? null,
                    'employee_code' => $data['employee_code'] ?? null,
                    'company_id' => $companyId,
                    'import_batch_id' => $batch->id,
                    'status' => 'active',
                ]);
                $importedCount++;
            } catch (\Exception $e) {
                $errors[] = ['row' => $rowNumber, 'reason' => 'Kayit hatasi: ' . $e->getMessage()];
                $skippedCount++;
            }
        }

        fclose($handle);

        // Update batch with final counts
        $batch->update([
            'imported_count' => $importedCount,
            'skipped_count' => $skippedCount,
        ]);

        // Audit log
        Audit::log('employees_bulk_import', 'employee', $batch->id, [
            'filename' => $file->getClientOriginalName(),
            'imported_count' => $importedCount,
            'skipped_count' => $skippedCount,
            'errors_count' => count($errors),
        ], $request);

        return response()->json([
            'success' => true,
            'data' => [
                'import_batch_id' => $batch->id,
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'errors' => $errors,
            ],
            'message' => "{$importedCount} calisan basariyla eklendi.",
        ]);
    }

    public function getLatestImportBatch(Request $request): JsonResponse
    {
        $batch = EmployeeImportBatch::where('company_id', $request->user()->company_id)
            ->where('is_rolled_back', false)
            ->where('imported_count', '>', 0)
            ->latest()
            ->first();

        if (!$batch) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $batch->id,
                'filename' => $batch->filename,
                'imported_count' => $batch->imported_count,
                'created_at' => $batch->created_at->toIso8601String(),
            ],
        ]);
    }

    public function rollbackImport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'import_batch_id' => 'required|integer',
        ]);

        $batch = EmployeeImportBatch::where('company_id', $request->user()->company_id)
            ->where('id', $validated['import_batch_id'])
            ->where('is_rolled_back', false)
            ->first();

        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Import batch bulunamadi veya zaten geri alinmis.',
            ], 404);
        }

        // Delete all employees from this batch
        $deletedCount = Employee::where('import_batch_id', $batch->id)->delete();

        // Mark batch as rolled back
        $batch->update([
            'is_rolled_back' => true,
            'rolled_back_at' => now(),
        ]);

        // Audit log
        Audit::log('employees_bulk_rollback', 'employee', $batch->id, [
            'filename' => $batch->filename,
            'deleted_count' => $deletedCount,
        ], $request);

        return response()->json([
            'success' => true,
            'data' => [
                'deleted_count' => $deletedCount,
            ],
            'message' => "{$deletedCount} calisan silindi.",
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
