<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    /**
     * Handle an incoming request.
     * Validates Bearer token for API access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $validToken = config('services.talentqx.api_token');

        // If no token configured, skip auth (development mode)
        if (empty($validToken)) {
            return $next($request);
        }

        // Check for Authorization header
        if (empty($token)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing Authorization header'
            ], 401);
        }

        // Validate token
        if ($token !== $validToken) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid API token'
            ], 401);
        }

        return $next($request);
    }
}
