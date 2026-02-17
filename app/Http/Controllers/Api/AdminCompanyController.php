<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use App\Models\AuditLog;
use App\Models\Company;
use App\Services\AI\AiSettingService;
use App\Services\AI\LLMProviderFactory;
use App\Services\Billing\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Admin Company Controller
 *
 * Platform admin endpoints for managing company subscriptions and AI settings.
 * All routes require platform.admin middleware.
 */
class AdminCompanyController extends Controller
{
    public function __construct(
        private CreditService $creditService,
        private AiSettingService $aiSettingService
    ) {}

    /**
     * GET /v1/admin/companies
     * List all companies with subscription info.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Company::query();

        // Search filter (name, slug)
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Plan filter
        if ($request->filled('plan')) {
            $query->where('subscription_plan', $request->plan);
        }

        // Premium filter
        if ($request->has('premium')) {
            $query->where('is_premium', filter_var($request->premium, FILTER_VALIDATE_BOOLEAN));
        }

        // Status filter (computed - requires post-query filtering)
        $statusFilter = $request->status;

        $companies = $query->orderBy('name')->get();

        // Apply status filter if specified
        if ($statusFilter) {
            $companies = $companies->filter(function ($company) use ($statusFilter) {
                return $company->getSubscriptionStatus() === $statusFilter;
            })->values();
        }

        // Format response
        $data = $companies->map(fn ($company) => $this->formatCompany($company));

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $data->count(),
            ],
        ]);
    }

    /**
     * GET /v1/admin/companies/{id}
     * Get single company with full subscription details.
     */
    public function show(string $id): JsonResponse
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Company not found.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatCompany($company, true),
        ]);
    }

    /**
     * PATCH /v1/admin/companies/{id}/subscription
     * Update company subscription fields.
     */
    public function updateSubscription(Request $request, string $id): JsonResponse
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Company not found.',
                ],
            ], 404);
        }

        $validated = $request->validate([
            'subscription_plan' => ['sometimes', 'nullable', Rule::in(Company::PLANS)],
            'subscription_ends_at' => ['sometimes', 'nullable', 'date'],
            'is_premium' => ['sometimes', 'boolean'],
            'grace_period_ends_at' => ['sometimes', 'nullable', 'date'],
        ]);

        // Validate grace_period_ends_at is after subscription_ends_at
        if (isset($validated['grace_period_ends_at']) && isset($validated['subscription_ends_at'])) {
            $subEnd = \Carbon\Carbon::parse($validated['subscription_ends_at']);
            $graceEnd = \Carbon\Carbon::parse($validated['grace_period_ends_at']);

            if ($graceEnd->lte($subEnd)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'validation_error',
                        'message' => 'Grace period end date must be after subscription end date.',
                    ],
                ], 422);
            }
        }

        // Capture old values for audit
        $oldValues = [
            'subscription_plan' => $company->subscription_plan,
            'subscription_ends_at' => $company->subscription_ends_at?->toIso8601String(),
            'is_premium' => $company->is_premium,
            'grace_period_ends_at' => $company->grace_period_ends_at?->toIso8601String(),
        ];

        DB::beginTransaction();
        try {
            // Update company
            $company->update($validated);
            $company->refresh();

            // Capture new values
            $newValues = [
                'subscription_plan' => $company->subscription_plan,
                'subscription_ends_at' => $company->subscription_ends_at?->toIso8601String(),
                'is_premium' => $company->is_premium,
                'grace_period_ends_at' => $company->grace_period_ends_at?->toIso8601String(),
            ];

            // Create audit log
            AuditLog::create([
                'user_id' => $request->user()->id,
                'company_id' => $company->id,
                'action' => 'admin.subscription.update',
                'entity_type' => 'company',
                'entity_id' => $company->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            Log::info('Admin updated company subscription', [
                'admin_id' => $request->user()->id,
                'company_id' => $company->id,
                'changes' => array_diff_assoc($newValues, $oldValues),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription updated successfully.',
                'data' => $this->formatCompany($company, true),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin subscription update failed', [
                'company_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'server_error',
                    'message' => 'Failed to update subscription.',
                ],
            ], 500);
        }
    }

    /**
     * PATCH /v1/admin/companies/{id}/credits
     * Update company credits (add bonus credits).
     */
    public function updateCredits(Request $request, string $id): JsonResponse
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Company not found.',
                ],
            ], 404);
        }

        $validated = $request->validate([
            'bonus_credits' => ['required', 'integer', 'min:1', 'max:10000'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->creditService->addBonusCredits(
                $company,
                $validated['bonus_credits'],
                $validated['reason'],
                $request->user()->id
            );

            $company->refresh();

            Log::info('Admin added bonus credits', [
                'admin_id' => $request->user()->id,
                'company_id' => $company->id,
                'amount' => $validated['bonus_credits'],
                'reason' => $validated['reason'],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Bonus kontür eklendi: {$validated['bonus_credits']}",
                'data' => $this->formatCompany($company, true),
            ]);

        } catch (\Exception $e) {
            Log::error('Admin credit update failed', [
                'company_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'server_error',
                    'message' => 'Failed to update credits.',
                ],
            ], 500);
        }
    }

    /**
     * GET /v1/admin/companies/{id}/credit-history
     * Get credit usage history for a company.
     */
    public function creditHistory(Request $request, string $id): JsonResponse
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Company not found.',
                ],
            ], 404);
        }

        $limit = $request->get('limit', 50);
        $history = $this->creditService->getUsageHistory($company, $limit);

        return response()->json([
            'success' => true,
            'data' => [
                'credits' => $this->creditService->getCreditStatus($company),
                'history' => $history,
            ],
        ]);
    }

    /**
     * GET /v1/admin/ai-settings
     * Get platform-wide AI settings.
     */
    public function getAiSettings(): JsonResponse
    {
        $settings = $this->aiSettingService->getPlatformSettings();

        return response()->json([
            'success' => true,
            'data' => $this->aiSettingService->formatForResponse($settings),
        ]);
    }

    /**
     * PATCH /v1/admin/ai-settings
     * Update platform-wide AI settings (API keys, models, etc).
     */
    public function updateAiSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['sometimes', 'string', Rule::in(AiSetting::PROVIDERS)],
            'openai_api_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'openai_model' => ['sometimes', 'string', 'max:100'],
            'openai_whisper_model' => ['sometimes', 'string', 'max:100'],
            'openai_enabled' => ['sometimes', 'boolean'],
            'kimi_api_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'kimi_base_url' => ['sometimes', 'string', 'max:255'],
            'kimi_model' => ['sometimes', 'string', 'max:100'],
            'kimi_enabled' => ['sometimes', 'boolean'],
            'timeout' => ['sometimes', 'integer', 'min:30', 'max:300'],
        ]);

        try {
            $settings = $this->aiSettingService->updatePlatformSettings(
                $validated,
                $request->user()
            );

            Log::info('Admin updated platform AI settings', [
                'admin_id' => $request->user()->id,
                'provider' => $settings->provider,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'AI settings updated successfully.',
                'data' => $this->aiSettingService->formatForResponse($settings),
            ]);

        } catch (\Exception $e) {
            Log::error('Admin AI settings update failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'server_error',
                    'message' => 'Failed to update AI settings.',
                ],
            ], 500);
        }
    }

    /**
     * GET /v1/admin/ai-providers
     * Get available AI providers and their status.
     */
    public function getAiProviders(): JsonResponse
    {
        $providers = $this->aiSettingService->getAvailableProviders();
        $settings = $this->aiSettingService->getPlatformSettings();

        return response()->json([
            'success' => true,
            'data' => [
                'providers' => $providers,
                'default_provider' => $settings->provider,
                'settings' => $this->aiSettingService->formatForResponse($settings),
            ],
        ]);
    }

    /**
     * GET /v1/admin/ai-providers/enabled
     * Get only enabled AI providers for dropdown.
     */
    public function getEnabledProviders(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $providers = $this->aiSettingService->getEnabledProviders($companyId);

        return response()->json([
            'success' => true,
            'data' => $providers,
        ]);
    }

    /**
     * PATCH /v1/admin/companies/{id}/ai-settings
     * Update company-specific AI settings.
     */
    public function updateCompanyAiSettings(Request $request, string $id): JsonResponse
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Company not found.',
                ],
            ], 404);
        }

        $validated = $request->validate([
            'provider' => ['required', 'string', Rule::in(AiSetting::PROVIDERS)],
        ]);

        // Check if the provider is available
        $providers = $this->aiSettingService->getAvailableProviders();
        $selectedProvider = collect($providers)->firstWhere('id', $validated['provider']);

        if (!$selectedProvider || !$selectedProvider['available']) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'provider_unavailable',
                    'message' => "AI provider '{$validated['provider']}' is not configured. Please set the API key first.",
                ],
            ], 422);
        }

        try {
            $settings = $this->aiSettingService->updateCompanySettings(
                $company->id,
                $validated,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Company AI settings updated successfully.',
                'data' => [
                    'ai_settings' => $this->aiSettingService->formatForResponse($settings),
                    'company' => $this->formatCompany($company, true),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Admin company AI settings update failed', [
                'company_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'server_error',
                    'message' => 'Failed to update AI settings.',
                ],
            ], 500);
        }
    }

    /**
     * POST /v1/admin/ai-providers/{provider}/test
     * Test AI provider connectivity.
     */
    public function testAiProvider(string $provider): JsonResponse
    {
        if (!LLMProviderFactory::isValidProvider($provider)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'invalid_provider',
                    'message' => "Invalid AI provider: {$provider}",
                ],
            ], 400);
        }

        $result = $this->aiSettingService->testProvider($provider);

        return response()->json([
            'success' => $result['success'] ?? false,
            'data' => $result,
        ]);
    }

    /**
     * Format company for API response.
     */
    private function formatCompany(Company $company, bool $detailed = false): array
    {
        $data = [
            'id' => $company->id,
            'name' => $company->name,
            'slug' => $company->slug,
            'subscription_plan' => $company->subscription_plan,
            'is_premium' => $company->is_premium,
            'subscription_ends_at' => $company->subscription_ends_at?->toIso8601String(),
            'grace_period_ends_at' => $company->grace_period_ends_at?->toIso8601String(),
            'computed_status' => $company->getComputedStatus(),
            'credits' => $this->creditService->getCreditStatus($company),
            'updated_at' => $company->updated_at->toIso8601String(),
        ];

        if ($detailed) {
            $data['logo_url'] = $company->logo_url;
            $data['city'] = $company->city;
            $data['country'] = $company->country;
            $data['created_at'] = $company->created_at->toIso8601String();
            $data['user_count'] = $company->users()->count();
            $data['job_count'] = $company->jobs()->count();
            $data['candidate_count'] = $company->candidates()->count();
            $data['recent_applications_count'] = $company->candidates()
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
            $data['has_marketplace_access'] = $company->hasMarketplaceAccess();
            $data['credit_history'] = $this->creditService->getUsageHistory($company, 10);

            // Get company AI settings
            $aiSettings = AiSetting::getForCompany($company->id);
            if ($aiSettings) {
                $data['ai_settings'] = $this->aiSettingService->formatForResponse($aiSettings);
            } else {
                $platformSettings = $this->aiSettingService->getPlatformSettings();
                $data['ai_settings'] = $this->aiSettingService->formatForResponse($platformSettings);
                $data['ai_settings']['using_platform_defaults'] = true;
            }
        }

        return $data;
    }
}
