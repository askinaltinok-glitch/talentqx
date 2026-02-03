<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Admin Company Controller
 *
 * Platform admin endpoints for managing company subscriptions.
 * All routes require platform.admin middleware.
 */
class AdminCompanyController extends Controller
{
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
        }

        return $data;
    }
}
