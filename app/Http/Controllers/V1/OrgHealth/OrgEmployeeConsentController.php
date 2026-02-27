<?php

namespace App\Http\Controllers\V1\OrgHealth;

use App\Http\Controllers\Controller;
use App\Jobs\OrgHealthDeleteEmployeeDataJob;
use App\Models\OrgEmployee;
use App\Models\OrgEmployeeConsent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrgEmployeeConsentController extends Controller
{
    public function upsert(Request $request, string $employeeId)
    {
        $data = $request->validate([
            'consent_version' => ['required','string'],
            'action' => ['required', Rule::in(['accept','withdraw','delete_request'])],
        ]);

        $tenantId = $request->user()->company_id;

        $employee = OrgEmployee::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $employeeId)
            ->firstOrFail();

        $consent = OrgEmployeeConsent::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employee->id)
            ->where('consent_version', $data['consent_version'])
            ->first();

        if (!$consent) {
            $consent = OrgEmployeeConsent::create([
                'tenant_id' => $tenantId,
                'employee_id' => $employee->id,
                'consent_version' => $data['consent_version'],
                'created_at' => now(),
            ]);
        }

        if ($data['action'] === 'accept') {
            $consent->consented_at = $consent->consented_at ?: now();
            $consent->withdrawn_at = null;
        }

        if ($data['action'] === 'withdraw') {
            $consent->withdrawn_at = now();
        }

        if ($data['action'] === 'delete_request') {
            $consent->delete_requested_at = now();
            OrgHealthDeleteEmployeeDataJob::dispatch($tenantId, $employee->id);
        }

        $consent->save();

        return response()->json([
            'employee_id' => $employee->id,
            'consent_version' => $consent->consent_version,
            'consented_at' => $consent->consented_at,
            'withdrawn_at' => $consent->withdrawn_at,
            'delete_requested_at' => $consent->delete_requested_at,
        ]);
    }
}
