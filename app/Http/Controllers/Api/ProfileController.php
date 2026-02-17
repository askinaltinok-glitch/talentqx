<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Get current user profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('company');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'company' => $user->company ? [
                    'id' => $user->company->id,
                    'name' => $user->company->name,
                    'logo_url' => $user->company->logo_url,
                ] : null,
            ],
        ]);
    }

    /**
     * Update user profile.
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'phone' => 'sometimes|nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Doğrulama hatası',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $user->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profil güncellendi',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
            ],
        ]);
    }

    /**
     * Get billing info.
     */
    public function getBilling(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Şirket bulunamadı',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'company_name' => $company->name,
                'legal_name' => $company->legal_name,
                'billing_type' => $company->billing_type ?? 'individual',
                'tax_number' => $company->tax_number,
                'tax_office' => $company->tax_office,
                'billing_address' => $company->billing_address,
                'billing_city' => $company->billing_city,
                'billing_postal_code' => $company->billing_postal_code,
                'billing_email' => $company->billing_email ?: $user->email,
                'has_complete_billing' => $company->hasBillingInfo(),
            ],
        ]);
    }

    /**
     * Update billing info.
     */
    public function updateBilling(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Şirket bulunamadı',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'billing_type' => 'required|in:individual,corporate',
            'legal_name' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:20',
            'tax_office' => 'nullable|string|max:100',
            'billing_address' => 'nullable|string|max:500',
            'billing_city' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:10',
            'billing_email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Doğrulama hatası',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Validate corporate billing requires tax info
        if ($data['billing_type'] === 'corporate') {
            if (empty($data['tax_number'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kurumsal fatura için vergi numarası zorunludur',
                    'errors' => ['tax_number' => ['Vergi numarası zorunludur']],
                ], 422);
            }
            if (empty($data['tax_office'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kurumsal fatura için vergi dairesi zorunludur',
                    'errors' => ['tax_office' => ['Vergi dairesi zorunludur']],
                ], 422);
            }
        }

        $company->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Fatura bilgileri güncellendi',
            'data' => [
                'billing_type' => $company->billing_type,
                'legal_name' => $company->legal_name,
                'tax_number' => $company->tax_number,
                'tax_office' => $company->tax_office,
                'billing_address' => $company->billing_address,
                'billing_city' => $company->billing_city,
                'billing_postal_code' => $company->billing_postal_code,
                'billing_email' => $company->billing_email,
                'has_complete_billing' => $company->hasBillingInfo(),
            ],
        ]);
    }

    /**
     * Request password reset email (for logged-in users).
     */
    public function requestPasswordReset(Request $request): JsonResponse
    {
        $user = $request->user();

        // Use the existing password reset service
        $passwordService = app(\App\Services\Auth\PasswordResetService::class);
        $passwordService->sendResetEmail($user);

        return response()->json([
            'success' => true,
            'message' => 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi',
        ]);
    }
}
