<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequirePlatformAdmin
{
    /**
     * Handle an incoming request.
     * Only allows access to users with is_platform_admin = true.
     * Returns 403 JSON for non-platform users.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        // Check user has platform admin flag
        $isPlatformAdmin = $user && $user->is_platform_admin === true;

        // Check token is wildcard ['*'] (TalentQX admin token) — blocks scoped tokens like ['octopus.admin']
        $isWildcard = $token && in_array('*', $token->abilities ?? []);

        if (!$isPlatformAdmin || !$isWildcard) {
            Log::warning('Unauthorized platform admin access attempt', [
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'company_id' => $user?->company_id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
                'method' => $request->method(),
                'token_abilities' => $token?->abilities ?? [],
            ]);

            return response()->json([
                'success' => false,
                'error' => 'forbidden',
                'message' => 'Platform admin access required. You do not have permission to access this resource.',
            ], 403);
        }

        return $next($request);
    }
}
