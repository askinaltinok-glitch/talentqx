<?php

namespace App\Http\Controllers\Api\CompanyPanel;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CreditPackage;
use App\Models\Payment;
use App\Services\Payment\IyzicoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentLinkMail;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $query = Payment::with(['company:id,name', 'package:id,name,credits'])
            ->select('id', 'company_id', 'package_id', 'payment_provider', 'status', 'amount', 'currency', 'credits_added', 'failure_reason', 'paid_at', 'created_at')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->paginate(25);

        return response()->json(['success' => true, 'data' => $payments]);
    }

    public function packages(Request $request): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $packages = CreditPackage::active()->sorted()->get();

        return response()->json(['success' => true, 'data' => $packages]);
    }

    public function companies(Request $request): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $query = Company::select('id', 'name', 'subscription_plan')
            ->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $companies = $query->limit(50)->get();

        return response()->json(['success' => true, 'data' => $companies]);
    }

    public function sendLink(Request $request): JsonResponse
    {
        $this->requireSalesOrAdmin($request);

        $request->validate([
            'company_id' => 'required|uuid|exists:companies,id',
            'package_id' => 'required|uuid|exists:credit_packages,id',
            'recipient_email' => 'required|email',
            'currency' => 'sometimes|in:TRY,USD,EUR',
        ]);

        $company = Company::findOrFail($request->company_id);
        $package = CreditPackage::findOrFail($request->package_id);

        // Find or use a system user for the checkout
        $adminUser = $company->users()->where('is_active', true)->first();
        if (!$adminUser) {
            return response()->json([
                'success' => false,
                'message' => 'Şirketin aktif kullanıcısı bulunamadı.',
            ], 422);
        }

        $iyzico = app(IyzicoService::class);
        $result = $iyzico->initializeCheckout(
            $company,
            $adminUser,
            $package,
            $request->input('currency', 'TRY')
        );

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        // Send payment link email
        $checkoutUrl = $result['checkout_url'] ?? $result['data']['checkout_url'] ?? null;
        if ($checkoutUrl) {
            Mail::to($request->recipient_email)->send(
                new PaymentLinkMail($company, $package, $checkoutUrl, $request->input('currency', 'TRY'))
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Ödeme linki gönderildi.',
            'data' => [
                'recipient' => $request->recipient_email,
                'amount' => $package->getPrice($request->input('currency', 'TRY')),
                'currency' => $request->input('currency', 'TRY'),
            ],
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
