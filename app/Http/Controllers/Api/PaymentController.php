<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use App\Models\Payment;
use App\Services\Payment\IyzicoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(
        private readonly IyzicoService $iyzicoService
    ) {}

    /**
     * Initialize checkout for a package.
     */
    public function checkout(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|uuid|exists:credit_packages,id',
            'currency' => 'sometimes|in:TRY,EUR',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Doğrulama hatası',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Şirket bulunamadı',
            ], 404);
        }

        // Check if billing info is complete for corporate
        if ($company->billing_type === 'corporate' && !$company->hasBillingInfo()) {
            return response()->json([
                'success' => false,
                'message' => 'Lütfen önce fatura bilgilerinizi tamamlayın',
                'code' => 'BILLING_INFO_REQUIRED',
            ], 400);
        }

        $package = CreditPackage::find($request->package_id);

        if (!$package || !$package->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Paket bulunamadı veya aktif değil',
            ], 404);
        }

        $currency = $request->get('currency', 'TRY');

        try {
            $result = $this->iyzicoService->initializeCheckout(
                company: $company,
                user: $user,
                package: $package,
                currency: $currency
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Ödeme başlatılamadı',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'checkout_url' => $result['checkout_url'] ?? null,
                    'checkout_form' => $result['checkout_form'] ?? null,
                    'payment_id' => $result['payment_id'],
                    'conversation_id' => $result['conversation_id'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Payment checkout failed', [
                'company_id' => $company->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ödeme işlemi başlatılamadı',
            ], 500);
        }
    }

    /**
     * Handle payment callback from İyzico.
     */
    public function callback(Request $request): JsonResponse
    {
        $token = $request->input('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token eksik',
            ], 400);
        }

        try {
            $result = $this->iyzicoService->handleCallback($token);

            if (!$result['success']) {
                Log::warning('Payment callback failed', [
                    'token' => $token,
                    'result' => $result,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Ödeme doğrulanamadı',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Ödeme başarılı',
                'data' => [
                    'payment_id' => $result['payment_id'],
                    'credits_added' => $result['credits_added'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Payment callback error', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ödeme işlenirken hata oluştu',
            ], 500);
        }
    }

    /**
     * Get payment history for current company.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Şirket bulunamadı',
            ], 404);
        }

        $payments = Payment::where('company_id', $company->id)
            ->with('package:id,name,credits')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn($payment) => $payment->toApiResponse());

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    /**
     * Get single payment details.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        $payment = Payment::where('id', $id)
            ->where('company_id', $company->id)
            ->with(['package', 'invoice'])
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Ödeme bulunamadı',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                ...$payment->toApiResponse(),
                'invoice' => $payment->invoice?->toApiResponse(),
            ],
        ]);
    }
}
