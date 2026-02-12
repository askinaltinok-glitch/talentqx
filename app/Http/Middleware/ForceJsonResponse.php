<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force JSON responses for API routes.
 *
 * This middleware ensures that:
 * 1. All API requests are treated as expecting JSON
 * 2. No redirects (302) are returned - only proper JSON error responses
 * 3. Missing Accept header defaults to application/json
 *
 * Applied to /api/* routes to prevent browser-style redirects
 * when Accept header is missing or incorrect.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        // Force Accept header to application/json for API routes
        // This ensures Laravel's exception handler returns JSON, not redirects
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
