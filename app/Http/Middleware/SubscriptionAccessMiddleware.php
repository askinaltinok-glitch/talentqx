<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionAccessMiddleware
{
    /**
     * Route patterns that are always allowed (even with expired subscription).
     */
    private const ALWAYS_ALLOWED_PATTERNS = [
        'v1/auth/*',
        'v1/change-password',
    ];

    /**
     * Route patterns allowed during grace period (read-only + export).
     * These patterns use glob-style matching.
     */
    private const GRACE_PERIOD_ALLOWED_PATTERNS = [
        // Candidates
        'v1/candidates',           // GET list
        'v1/candidates/*',         // GET show, GET export
        'v1/candidates/*/export',  // GET export

        // Employees
        'v1/employees',            // GET list
        'v1/employees/*',          // GET show
        'v1/employees/*/export',   // GET export

        // Jobs
        'v1/jobs',                 // GET list
        'v1/jobs/*',               // GET show

        // Interviews
        'v1/interviews/*',         // GET show

        // Dashboard
        'v1/dashboard/*',          // GET stats

        // Reports
        'v1/reports/*',            // GET download

        // KVKK
        'v1/kvkk/*',               // GET audit logs
    ];

    /**
     * HTTP methods allowed during grace period.
     */
    private const GRACE_PERIOD_ALLOWED_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * Handle an incoming request.
     *
     * Logic:
     * 1. Platform admin → bypass (full access)
     * 2. subscription_active = true → full access
     * 3. subscription expired + 60 days (grace period) → read-only + export
     * 4. grace period expired → 403 + "subscription_required"
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // No user = should be handled by auth middleware
        if (!$user) {
            return $next($request);
        }

        // Platform admins bypass all subscription restrictions
        if ($user->is_platform_admin) {
            return $next($request);
        }

        $company = $user->company;

        // No company associated = deny access
        if (!$company) {
            return $this->denyAccess('subscription_required', 'No company associated with this account.', 403);
        }

        $path = $request->path();
        $method = $request->method();

        // Always allowed routes (auth, password change)
        if ($this->isAlwaysAllowed($path)) {
            return $next($request);
        }

        // Active subscription = full access
        if ($company->isSubscriptionActive()) {
            return $next($request);
        }

        // Check if in grace period
        if ($company->isInGracePeriod()) {
            // Grace period: only allow read operations on specific routes
            if ($this->isGracePeriodAllowed($path, $method)) {
                return $next($request);
            }

            // Log the blocked write attempt during grace period
            Log::warning('Grace period write attempt blocked', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'path' => $path,
                'method' => $method,
                'grace_period_ends_at' => $company->getGracePeriodEndDate()?->toIso8601String(),
            ]);

            return $this->denyAccess(
                'grace_period_restricted',
                'Your subscription has expired. During the grace period, only read operations and exports are allowed.',
                403,
                [
                    'grace_period_ends_at' => $company->getGracePeriodEndDate()?->toIso8601String(),
                ]
            );
        }

        // Subscription and grace period both expired
        Log::warning('Subscription required - access denied', [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'path' => $path,
            'method' => $method,
            'subscription_ended_at' => $company->subscription_ends_at?->toIso8601String(),
        ]);

        return $this->denyAccess(
            'subscription_required',
            'Your subscription has expired and the grace period has ended. Please renew your subscription to continue.',
            403,
            [
                'subscription_ended_at' => $company->subscription_ends_at?->toIso8601String(),
                'grace_period_ended_at' => $company->getGracePeriodEndDate()?->toIso8601String(),
            ]
        );
    }

    /**
     * Check if the path is always allowed (auth routes).
     */
    private function isAlwaysAllowed(string $path): bool
    {
        // Remove leading 'api/' if present
        $path = preg_replace('#^api/#', '', $path);

        foreach (self::ALWAYS_ALLOWED_PATTERNS as $pattern) {
            if ($this->pathMatchesPattern($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the path and method are allowed during grace period.
     */
    private function isGracePeriodAllowed(string $path, string $method): bool
    {
        // Only read methods allowed
        if (!in_array($method, self::GRACE_PERIOD_ALLOWED_METHODS)) {
            return false;
        }

        // Remove leading 'api/' if present
        $path = preg_replace('#^api/#', '', $path);

        // Auth routes are always allowed
        if ($this->isAlwaysAllowed($path)) {
            return true;
        }

        foreach (self::GRACE_PERIOD_ALLOWED_PATTERNS as $pattern) {
            if ($this->pathMatchesPattern($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a path matches a glob-style pattern.
     */
    private function pathMatchesPattern(string $path, string $pattern): bool
    {
        // Convert glob pattern to regex
        $regex = str_replace(
            ['*', '/'],
            ['[^/]*', '\/'],
            $pattern
        );

        return (bool) preg_match('#^' . $regex . '$#', $path);
    }

    /**
     * Return a denial response.
     */
    private function denyAccess(string $code, string $message, int $status, array $extra = []): Response
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                ...$extra,
            ],
        ], $status);
    }
}
