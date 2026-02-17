<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequireOctopusAdmin
{
    /**
     * Handle an incoming request.
     * Requires: is_octopus_admin=true, token has 'octopus.admin' ability,
     * and token is NOT a wildcard ['*'] token (blocks cross-access from TalentQX admin).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        // Check 1: user must be octopus admin
        $isOctoAdmin = $user && $user->is_octopus_admin === true;

        // Check 2: token must have octopus.admin ability
        $hasAbility = $token && $token->can('octopus.admin');

        // Check 3: token must NOT be wildcard — Sanctum can() returns true for ['*']
        $isWildcard = $token && in_array('*', $token->abilities ?? []);

        if (!$isOctoAdmin || !$hasAbility || $isWildcard) {
            Log::warning('Unauthorized octopus admin access attempt', [
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'ip_address' => $request->ip(),
                'path' => $request->path(),
                'is_octopus_admin' => $user?->is_octopus_admin ?? false,
                'has_ability' => $hasAbility,
                'is_wildcard' => $isWildcard,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'forbidden',
                'message' => 'Octopus admin access required.',
            ], 403);
        }

        return $next($request);
    }
}
