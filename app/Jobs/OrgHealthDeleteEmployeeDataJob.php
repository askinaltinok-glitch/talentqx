<?php

namespace App\Jobs;

use App\Models\OrgEmployee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class OrgHealthDeleteEmployeeDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $tenantId, public string $employeeId) {}

    public function handle(): void
    {
        DB::transaction(function () {
            // Delete answers via assessments cascade; ensure order safe
            DB::table('org_workstyle_profiles')
                ->where('tenant_id', $this->tenantId)
                ->where('employee_id', $this->employeeId)
                ->delete();

            DB::table('org_assessment_answers')
                ->whereIn('assessment_id', function ($q) {
                    $q->select('id')
                      ->from('org_assessments')
                      ->where('tenant_id', $this->tenantId)
                      ->where('employee_id', $this->employeeId);
                })
                ->delete();

            DB::table('org_assessments')
                ->where('tenant_id', $this->tenantId)
                ->where('employee_id', $this->employeeId)
                ->delete();

            DB::table('org_employee_consents')
                ->where('tenant_id', $this->tenantId)
                ->where('employee_id', $this->employeeId)
                ->delete();

            // If OrgHealth-only identity, delete employee row too:
            DB::table('org_employees')
                ->where('tenant_id', $this->tenantId)
                ->where('id', $this->employeeId)
                ->delete();
        });
    }
}
