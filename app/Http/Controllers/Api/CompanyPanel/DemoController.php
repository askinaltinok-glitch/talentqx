<?php

namespace App\Http\Controllers\Api\CompanyPanel;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoController extends Controller
{
    public function context(Request $request, string $id): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $lead = CrmLead::with(['company', 'contact'])->findOrFail($id);

        $companyData = null;
        if ($lead->company) {
            $company = Company::find($lead->company->linked_company_id);
            if ($company) {
                $companyData = [
                    'id' => $company->id,
                    'name' => $company->name,
                    'subscription_plan' => $company->subscription_plan,
                    'credits_used' => $company->credits_used,
                    'total_interviews' => $company->formInterviews()->count(),
                    'total_candidates' => $company->candidates()->count(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'lead' => $lead,
                'company' => $companyData,
            ],
        ]);
    }

    public function createAccount(Request $request, string $id): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $lead = CrmLead::with('contact')->findOrFail($id);

        $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_email' => 'required|email',
            'contact_name' => 'required|string|max:255',
            'plan' => 'required|in:trial,starter,professional,enterprise',
        ]);

        // Create company
        $company = Company::create([
            'name' => $request->company_name,
            'subscription_plan' => $request->plan,
            'subscription_ends_at' => now()->addDays($request->plan === 'trial' ? 14 : 365),
            'is_active' => true,
        ]);

        // Create admin user for the company
        $nameParts = explode(' ', $request->contact_name, 2);
        $tempPassword = Str::random(12);
        $user = User::create([
            'company_id' => $company->id,
            'email' => Str::lower($request->contact_email),
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
            'password' => $tempPassword,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        // Log activity on lead
        $lead->addActivity('demo_account_created', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'plan' => $request->plan,
        ], $request->user()->id);

        return response()->json([
            'success' => true,
            'data' => [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'temp_password' => $tempPassword,
                'message' => 'Demo hesabı oluşturuldu.',
            ],
        ], 201);
    }

    public function setPackage(Request $request, string $id): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $lead = CrmLead::findOrFail($id);

        $request->validate([
            'package' => 'required|in:starter,professional,enterprise',
            'currency' => 'sometimes|in:TRY,USD,EUR',
        ]);

        $lead->addActivity('package_selected', [
            'package' => $request->package,
            'currency' => $request->input('currency', 'TRY'),
        ], $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Paket tercihi kaydedildi.',
        ]);
    }

    public function scheduleAppointment(Request $request, string $id): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $lead = CrmLead::findOrFail($id);

        $request->validate([
            'starts_at' => 'required|date|after:now',
            'notes' => 'nullable|string',
        ]);

        $lead->update(['next_follow_up_at' => $request->starts_at]);

        $lead->addActivity('appointment_scheduled', [
            'starts_at' => $request->starts_at,
            'notes' => $request->notes,
        ], $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Randevu planlandı.',
        ]);
    }

    private function requireSalesOrAdmin(Request $request): void
    {
        $role = $request->user()->company_panel_role;
        if (!in_array($role, ['super_admin', 'sales_rep'])) {
            abort(403, 'Bu işlem için yetkiniz yok.');
        }
    }
}
