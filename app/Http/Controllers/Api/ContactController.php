<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\Privacy\ConsentService;
use App\Services\Privacy\RegimeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    private ConsentService $consentService;
    private RegimeResolver $regimeResolver;

    public function __construct(ConsentService $consentService, RegimeResolver $regimeResolver)
    {
        $this->consentService = $consentService;
        $this->regimeResolver = $regimeResolver;
    }

    /**
     * POST /api/v1/contact
     * Handle contact/demo form submissions
     */
    public function submit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'form_type' => 'required|in:demo,contact,sales,support',
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:2000',
            'employee_count' => 'nullable|integer|min:1',
            'consent_accepted' => 'required|accepted',
            'policy_version' => 'nullable|string|max:20',
            'locale' => 'nullable|string|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Validation failed',
                    'details' => $validator->errors(),
                ],
            ], 422);
        }

        $data = $validator->validated();

        // Verify consent is accepted
        if (empty($data['consent_accepted'])) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Privacy consent is required',
                    'code' => 'CONSENT_REQUIRED',
                ],
            ], 422);
        }

        try {
            // Create lead
            $lead = Lead::create([
                'company_name' => $data['company_name'],
                'contact_name' => $data['contact_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'employee_count' => $data['employee_count'] ?? null,
                'notes' => $data['message'] ?? null,
                'source' => 'website_' . $data['form_type'],
                'status' => 'new',
                'priority' => 'medium',
            ]);

            // Record privacy consent (regime resolved server-side)
            $consent = $this->consentService->record([
                'subject_type' => 'lead',
                'subject_id' => $lead->id,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'full_name' => $data['contact_name'],
                'consent_type' => 'privacy_notice',
                'policy_version' => $data['policy_version'] ?? null,
                'source' => 'website_form',
                'form_type' => $data['form_type'],
            ], $request);

            Log::info('Contact form submitted', [
                'lead_id' => $lead->id,
                'consent_id' => $consent->id,
                'form_type' => $data['form_type'],
                'regime' => $consent->regime,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => $this->getSuccessMessage($data['form_type'], $consent->regime),
                    'lead_id' => $lead->id,
                    'consent_recorded' => true,
                    'regime' => $consent->regime,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Contact form submission failed', [
                'error' => $e->getMessage(),
                'email' => $data['email'],
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Failed to process your request. Please try again.',
                ],
            ], 500);
        }
    }

    /**
     * POST /api/v1/contact/newsletter
     * Handle newsletter subscription
     */
    public function newsletter(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'consent_accepted' => 'required|accepted',
            'locale' => 'nullable|string|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Validation failed',
                    'details' => $validator->errors(),
                ],
            ], 422);
        }

        $data = $validator->validated();

        try {
            // Record marketing consent
            $consent = $this->consentService->record([
                'subject_type' => 'visitor',
                'email' => $data['email'],
                'consent_type' => 'marketing',
                'source' => 'newsletter',
                'form_type' => 'newsletter',
            ], $request);

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => 'Successfully subscribed to newsletter',
                    'consent_recorded' => true,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Newsletter subscription failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => ['message' => 'Subscription failed'],
            ], 500);
        }
    }

    /**
     * Get success message based on form type and regime
     */
    private function getSuccessMessage(string $formType, string $regime): string
    {
        $messages = [
            'demo' => [
                'tr' => 'Demo talebiniz alındı. En kısa sürede sizinle iletişime geçeceğiz.',
                'en' => 'Your demo request has been received. We will contact you shortly.',
            ],
            'contact' => [
                'tr' => 'Mesajınız alındı. Teşekkür ederiz.',
                'en' => 'Your message has been received. Thank you.',
            ],
            'sales' => [
                'tr' => 'Satış ekibimiz sizinle en kısa sürede iletişime geçecektir.',
                'en' => 'Our sales team will contact you shortly.',
            ],
            'support' => [
                'tr' => 'Destek talebiniz oluşturuldu.',
                'en' => 'Your support request has been created.',
            ],
        ];

        $locale = $regime === 'KVKK' ? 'tr' : 'en';
        return $messages[$formType][$locale] ?? $messages['contact']['en'];
    }
}
