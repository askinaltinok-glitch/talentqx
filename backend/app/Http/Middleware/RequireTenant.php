<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTenant
{
    /**
     * Handle an incoming request.
     * Ensures a tenant context is set before proceeding.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->bound('current_tenant_id') || !app('current_tenant_id')) {
            return response()->json([
                'error' => 'Tenant context required',
                'message' => 'This endpoint requires a valid tenant context. Please authenticate or specify tenant.',
            ], 403);
        }

        return $next($request);
    }
}
