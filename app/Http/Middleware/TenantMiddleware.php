<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     * Resolves tenant from:
     * 1. Subdomain (e.g., ekler-istanbul.talentqx.com)
     * 2. X-Tenant-ID header
     * 3. Authenticated user's company_id
     * 4. URL parameter for public apply pages
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolveTenantId($request);

        if ($tenantId) {
            // Validate tenant exists
            $tenant = Company::find($tenantId);
            if ($tenant) {
                // Admins must NEVER be tenant-scoped — skip binding so
                // BelongsToTenant global scopes don't filter their queries.
                $user = $request->user();
                if ($user && ($user->is_platform_admin || $user->is_octopus_admin)) {
                    // Still expose tenant info on request for controllers that need it,
                    // but do NOT bind to the container (no auto-scoping).
                    $request->merge(['tenant_id' => $tenantId]);
                    return $next($request);
                }

                app()->instance('current_tenant_id', $tenantId);
                app()->instance('current_tenant', $tenant);

                // Add tenant info to request for easy access
                $request->merge(['tenant_id' => $tenantId]);
            }
        }

        return $next($request);
    }

    /**
     * Resolve tenant ID from various sources.
     */
    protected function resolveTenantId(Request $request): ?string
    {
        // 1. Try subdomain
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);
        if ($subdomain && $subdomain !== 'www' && $subdomain !== 'api') {
            $tenant = Company::where('slug', $subdomain)->first();
            if ($tenant) {
                return $tenant->id;
            }
        }

        // 2. Try X-Tenant-ID header
        $headerTenantId = $request->header('X-Tenant-ID');
        if ($headerTenantId) {
            return $headerTenantId;
        }

        // 3. Try authenticated user's company
        if ($request->user() && $request->user()->company_id) {
            return $request->user()->company_id;
        }

        // 4. Try route parameter (for public apply pages)
        $companySlug = $request->route('companySlug');
        if ($companySlug) {
            $tenant = Company::where('slug', $companySlug)->first();
            if ($tenant) {
                return $tenant->id;
            }
        }

        return null;
    }

    /**
     * Extract subdomain from hostname.
     */
    protected function extractSubdomain(string $host): ?string
    {
        $parts = explode('.', $host);

        // Handle localhost and IP addresses
        if (count($parts) < 2 || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        // For domains like "ekler-istanbul.talentqx.com"
        if (count($parts) >= 3) {
            return $parts[0];
        }

        return null;
    }
}
