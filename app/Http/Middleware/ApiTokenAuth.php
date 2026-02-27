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
        $validToken = config('services.talentqx.api_token');

        // If no token configured, skip auth (development mode)
        if (empty($validToken)) {
            return $next($request);
        }

        // Accept token from: Authorization: Bearer, X-API-Token header, or Sanctum session
        $token = $request->bearerToken() ?: $request->header('X-API-Token');

        // Static API token match — pass through
        if ($token && $token === $validToken) {
            return $next($request);
        }

        // Fallback: allow authenticated Sanctum users (admin panel users)
        if ($request->bearerToken() && $request->user('sanctum')) {
            return $next($request);
        }

        if (empty($token)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing Authorization header',
            ], 401);
        }

        return response()->json([
            'error' => 'Unauthorized',
            'message' => 'Invalid API token',
        ], 401);
    }
}
