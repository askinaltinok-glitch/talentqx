<?php

namespace App\Services\KVKK;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeErasureRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmployeeDataService
{
    /**
     * Erase employee data (Right to be Forgotten / KVKK compliance)
     */
    public function eraseEmployeeData(Employee $employee, string $reason, ?string $requestedBy = null): array
    {
        $erasedDataTypes = [];

        return DB::transaction(function () use ($employee, $reason, $requestedBy, &$erasedDataTypes) {
            // Create erasure request record
            $request = EmployeeErasureRequest::create([
                'employee_id' => $employee->id,
                'requested_by' => $requestedBy ?? auth()->id(),
                'request_type' => $this->determineRequestType($reason),
                'status' => EmployeeErasureRequest::STATUS_PROCESSING,
            ]);

            try {
                // 1. Erase assessment session data
                $this->eraseAssessmentSessions($employee);
                $erasedDataTypes[] = 'assessment_sessions';

                // 2. Erase assessment results (scores, analyses)
                $this->eraseAssessmentResults($employee);
                $erasedDataTypes[] = 'assessment_results';

                // 3. Erase personal information
                $this->erasePersonalInfo($employee, $reason);
                $erasedDataTypes[] = 'personal_info';

                // 4. Log the erasure
                AuditLog::create([
                    'action' => 'employee_data_erased',
                    'entity_type' => 'employee',
                    'entity_id' => $employee->id,
                    'user_id' => auth()->id(),
                    'company_id' => $employee->company_id,
                    'old_values' => [
                        'first_name' => '[REDACTED]',
                        'last_name' => '[REDACTED]',
                        'email' => '[REDACTED]',
                    ],
                    'new_values' => [
                        'is_erased' => true,
                        'erased_at' => now()->toIso8601String(),
                        'erasure_reason' => $reason,
                    ],
                    'metadata' => [
                        'erased_data_types' => $erasedDataTypes,
                        'request_id' => $request->id,
                    ],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                // Mark request as completed
                $request->markCompleted($erasedDataTypes);

                return [
                    'success' => true,
                    'erased_data_types' => $erasedDataTypes,
                    'request_id' => $request->id,
                ];
            } catch (\Exception $e) {
                Log::error('Employee data erasure failed', [
                    'employee_id' => $employee->id,
                    'error' => $e->getMessage(),
                ]);

                $request->markFailed($e->getMessage());

                throw $e;
            }
        });
    }

    /**
     * Export employee data (KVKK data portability)
     */
    public function exportEmployeeData(Employee $employee): array
    {
        $employee->load([
            'assessmentSessions.result',
            'assessmentSessions.template',
            'company',
        ]);

        // Log the export action
        AuditLog::create([
            'action' => 'employee_data_exported',
            'entity_type' => 'employee',
            'entity_id' => $employee->id,
            'user_id' => auth()->id(),
            'company_id' => $employee->company_id,
            'metadata' => [
                'export_format' => 'json',
                'exported_at' => now()->toIso8601String(),
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'department' => $employee->department,
                'current_role' => $employee->current_role,
                'branch' => $employee->branch,
                'hire_date' => $employee->hire_date?->toDateString(),
                'manager_name' => $employee->manager_name,
                'status' => $employee->status,
                'created_at' => $employee->created_at->toIso8601String(),
            ],
            'company' => $employee->company ? [
                'id' => $employee->company->id,
                'name' => $employee->company->name,
            ] : null,
            'assessments' => $employee->assessmentSessions->map(function ($session) {
                return [
                    'session_id' => $session->id,
                    'template_name' => $session->template?->name,
                    'status' => $session->status,
                    'started_at' => $session->started_at?->toIso8601String(),
                    'completed_at' => $session->completed_at?->toIso8601String(),
                    'time_spent_seconds' => $session->time_spent_seconds,
                    'responses' => $session->responses,
                    'result' => $session->result ? [
                        'overall_score' => $session->result->overall_score,
                        'competency_scores' => $session->result->competency_scores,
                        'risk_level' => $session->result->risk_level,
                        'level_label' => $session->result->level_label,
                        'strengths' => $session->result->strengths,
                        'improvement_areas' => $session->result->improvement_areas,
                        'development_plan' => $session->result->development_plan,
                        'promotion_suitable' => $session->result->promotion_suitable,
                        'promotion_readiness' => $session->result->promotion_readiness,
                        'analyzed_at' => $session->result->analyzed_at?->toIso8601String(),
                    ] : null,
                ];
            })->toArray(),
            'export_metadata' => [
                'exported_at' => now()->toIso8601String(),
                'format' => 'json',
                'version' => '1.0',
                'data_controller' => config('app.name'),
            ],
        ];
    }

    /**
     * Erase assessment sessions
     */
    private function eraseAssessmentSessions(Employee $employee): void
    {
        foreach ($employee->assessmentSessions as $session) {
            // Invalidate token
            $session->update([
                'access_token' => 'ERASED_' . bin2hex(random_bytes(16)),
                'token_expires_at' => now()->subYear(),
                'responses' => null,
                'device_info' => null,
                'ip_address' => null,
                'user_agent' => null,
            ]);
        }
    }

    /**
     * Erase assessment results
     */
    private function eraseAssessmentResults(Employee $employee): void
    {
        foreach ($employee->assessmentSessions as $session) {
            if ($session->result) {
                $session->result->update([
                    'raw_ai_response' => null,
                    'question_analyses' => null,
                    'validation_errors' => null,
                    'cheating_flags' => null,
                ]);
            }
        }
    }

    /**
     * Erase personal information
     */
    private function erasePersonalInfo(Employee $employee, string $reason): void
    {
        $employee->update([
            'first_name' => '[ERASED]',
            'last_name' => '[ERASED]',
            'email' => null,
            'phone' => null,
            'metadata' => null,
            'is_erased' => true,
            'erased_at' => now(),
            'erasure_reason' => $reason,
        ]);
    }

    /**
     * Determine request type from reason
     */
    private function determineRequestType(string $reason): string
    {
        $reason = strtolower($reason);

        if (str_contains($reason, 'kvkk') || str_contains($reason, 'gdpr')) {
            return EmployeeErasureRequest::TYPE_KVKK_REQUEST;
        }

        if (str_contains($reason, 'retention') || str_contains($reason, 'expired')) {
            return EmployeeErasureRequest::TYPE_RETENTION_EXPIRED;
        }

        if (str_contains($reason, 'employee') || str_contains($reason, 'request')) {
            return EmployeeErasureRequest::TYPE_EMPLOYEE_REQUEST;
        }

        return EmployeeErasureRequest::TYPE_COMPANY_POLICY;
    }

    /**
     * Process employees with expired retention
     */
    public function processExpiredRetention(): array
    {
        $processed = [];

        $expiredEmployees = Employee::where('is_erased', false)
            ->whereHas('assessmentSessions', function ($q) {
                $q->where('status', 'completed');
            })
            ->get()
            ->filter(function ($employee) {
                return $employee->isRetentionExpired();
            });

        foreach ($expiredEmployees as $employee) {
            try {
                $this->eraseEmployeeData($employee, 'retention_expired', null);
                $processed[] = [
                    'employee_id' => $employee->id,
                    'status' => 'erased',
                ];
            } catch (\Exception $e) {
                Log::error('Failed to process retention expiry', [
                    'employee_id' => $employee->id,
                    'error' => $e->getMessage(),
                ]);
                $processed[] = [
                    'employee_id' => $employee->id,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $processed;
    }

    /**
     * Get retention statistics
     */
    public function getRetentionStats(): array
    {
        $totalEmployees = Employee::count();
        $erasedEmployees = Employee::where('is_erased', true)->count();
        $activeEmployees = Employee::where('is_erased', false)->count();

        // Count employees approaching retention expiry (within 30 days)
        $approachingExpiry = Employee::where('is_erased', false)
            ->get()
            ->filter(function ($employee) {
                if (!$employee->hire_date) return false;
                $lastActivity = $employee->latestAssessment?->completed_at ?? $employee->hire_date;
                $expiryDate = $lastActivity->addDays($employee->retention_days ?? 180);
                return $expiryDate->isBetween(now(), now()->addDays(30));
            })
            ->count();

        return [
            'total_employees' => $totalEmployees,
            'erased_employees' => $erasedEmployees,
            'active_employees' => $activeEmployees,
            'approaching_expiry' => $approachingExpiry,
            'erasure_requests' => [
                'pending' => EmployeeErasureRequest::pending()->count(),
                'completed' => EmployeeErasureRequest::completed()->count(),
            ],
        ];
    }
}
