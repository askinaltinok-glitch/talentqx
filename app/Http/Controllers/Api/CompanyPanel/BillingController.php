<?php

namespace App\Http\Controllers\Api\CompanyPanel;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requireAccountingOrAdmin($request);

        $query = Company::select(
            'id', 'name', 'subscription_plan', 'subscription_ends_at',
            'monthly_credits', 'bonus_credits', 'credits_used',
            'credits_period_start', 'created_at'
        );

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('plan')) {
            $query->where('subscription_plan', $request->plan);
        }

        $companies = $query->orderByDesc('created_at')->paginate(25);

        // Enrich with remaining credits
        $companies->getCollection()->transform(function ($company) {
            $company->remaining_credits = $company->getRemainingCredits();
            $company->total_credits = $company->getTotalCredits();
            $company->subscription_status = $company->getSubscriptionStatus();
            return $company;
        });

        return response()->json(['success' => true, 'data' => $companies]);
    }

    public function stats(Request $request): JsonResponse
    {
        $this->requireAccountingOrAdmin($request);

        $totalRevenue = Payment::where('status', 'completed')
            ->where('currency', 'TRY')
            ->sum('amount');

        $monthlyRevenue = Payment::where('status', 'completed')
            ->where('currency', 'TRY')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        $activeSubscriptions = Company::where('subscription_ends_at', '>', now())
            ->count();

        $expiringThisMonth = Company::whereBetween('subscription_ends_at', [now(), now()->endOfMonth()])
            ->count();

        $totalCompanies = Company::count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue_try' => (float) $totalRevenue,
                'monthly_revenue_try' => (float) $monthlyRevenue,
                'active_subscriptions' => $activeSubscriptions,
                'expiring_this_month' => $expiringThisMonth,
                'total_companies' => $totalCompanies,
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->requireAccountingOrAdmin($request);

        $company = Company::findOrFail($id);

        $payments = Payment::where('company_id', $id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'subscription_plan' => $company->subscription_plan,
                    'subscription_ends_at' => $company->subscription_ends_at,
                    'credits_used' => $company->credits_used,
                    'remaining_credits' => $company->getRemainingCredits(),
                    'total_credits' => $company->getTotalCredits(),
                    'subscription_status' => $company->getSubscriptionStatus(),
                    'is_active' => $company->subscription_ends_at && $company->subscription_ends_at->isFuture(),
                    'created_at' => $company->created_at,
                    // Billing info
                    'legal_name' => $company->legal_name,
                    'billing_type' => $company->billing_type ?? 'individual',
                    'tax_number' => $company->tax_number,
                    'tax_office' => $company->tax_office,
                    'billing_address' => $company->billing_address,
                    'billing_city' => $company->billing_city,
                    'billing_postal_code' => $company->billing_postal_code,
                    'billing_email' => $company->billing_email,
                    'billing_phone' => $company->billing_phone,
                    'has_billing_info' => $company->hasBillingInfo(),
                ],
                'payments' => $payments,
            ],
        ]);
    }

    public function updateBillingInfo(Request $request, string $id): JsonResponse
    {
        $this->requireAccountingOrAdmin($request);

        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'legal_name' => 'nullable|string|max:255',
            'billing_type' => 'nullable|in:individual,corporate',
            'tax_number' => 'nullable|string|max:20',
            'tax_office' => 'nullable|string|max:100',
            'billing_address' => 'nullable|string|max:1000',
            'billing_city' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:10',
            'billing_email' => 'nullable|email|max:255',
            'billing_phone' => 'nullable|string|max:30',
        ]);

        $company->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cari bilgileri güncellendi.',
            'data' => [
                'legal_name' => $company->legal_name,
                'billing_type' => $company->billing_type,
                'tax_number' => $company->tax_number,
                'tax_office' => $company->tax_office,
                'billing_address' => $company->billing_address,
                'billing_city' => $company->billing_city,
                'billing_postal_code' => $company->billing_postal_code,
                'billing_email' => $company->billing_email,
                'billing_phone' => $company->billing_phone,
                'has_billing_info' => $company->hasBillingInfo(),
            ],
        ]);
    }

    private function requireAccountingOrAdmin(Request $request): void
    {
        $role = $request->user()->company_panel_role;
        if (!in_array($role, ['super_admin', 'accounting'])) {
            abort(403, 'Bu işlem için yetkiniz yok.');
        }
    }
}
