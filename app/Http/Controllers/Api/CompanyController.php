<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Get the current user's company subscription status.
     * Used by frontend to determine access level (FULL, READ_ONLY_EXPORT, LOCKED).
     */
    public function subscriptionStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        // Platform admins always have full access
        if ($user->is_platform_admin) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'active',
                    'plan' => 'platform_admin',
                    'is_premium' => true,
                    'has_marketplace_access' => true,
                    'subscription_ends_at' => null,
                    'grace_period_ends_at' => null,
                    'is_platform_admin' => true,
                ],
            ]);
        }

        $company = $user->company;

        if (!$company) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'expired',
                    'plan' => null,
                    'is_premium' => false,
                    'has_marketplace_access' => false,
                    'subscription_ends_at' => null,
                    'grace_period_ends_at' => null,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $company->getSubscriptionStatus(),
                'plan' => $company->subscription_plan,
                'is_premium' => $company->is_premium,
                'has_marketplace_access' => $company->hasMarketplaceAccess(),
                'subscription_ends_at' => $company->subscription_ends_at?->toISOString(),
                'grace_period_ends_at' => $company->grace_period_ends_at?->toISOString(),
            ],
        ]);
    }
}
