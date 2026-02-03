<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequireCustomerScope
{
    /**
     * API path prefixes allowed for company users (non-platform admins).
     * Default-deny: anything NOT in this list is blocked for company users.
     * Platform admins bypass this completely.
     */
    private const CUSTOMER_ALLOWED_PREFIXES = [
        // Auth & password management
        'v1/auth',
        'v1/change-password',

        // Core hiring workflow
        'v1/positions/templates',
        'v1/jobs',
        'v1/candidates',
        'v1/interviews',
        'v1/dashboard',

        // Reports (protected endpoints for generating/viewing)
        'v1/reports',

        // Marketplace (premium features)
        'v1/marketplace',
    ];

    /**
     * Handle an incoming request.
     *
     * DEFAULT-DENY approach:
     * - Platform admins: bypass all restrictions
     * - Company users: only allowed paths in CUSTOMER_ALLOWED_PREFIXES
     * - Everything else: 403 Forbidden
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // No user = should be handled by auth middleware
        if (!$user) {
            return $next($request);
        }

        // Platform admins bypass all restrictions
        if ($user->is_platform_admin) {
            return $next($request);
        }

        // Company user: check if path is in allowlist
        $path = $request->path();

        if (!$this->isPathAllowed($path)) {
            // Log unauthorized access attempt
            Log::warning('Customer scope violation: blocked path access', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'company_id' => $user->company_id,
                'ip_address' => $request->ip(),
                'path' => $path,
                'method' => $request->method(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'forbidden',
                'message' => 'Access denied. This feature is not available for your account type.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if the given path is allowed for company users.
     */
    private function isPathAllowed(string $path): bool
    {
        // Remove leading 'api/' if present (depends on how routes are set up)
        $path = preg_replace('#^api/#', '', $path);

        foreach (self::CUSTOMER_ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
