<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequireCompanyPanel
{
    /**
     * Requires: company_panel_role IS NOT NULL, token has 'company_panel.*' ability,
     * and token is NOT a wildcard ['*'] token (blocks cross-access from TalentQX admin).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        $hasRole = $user && $user->company_panel_role !== null;
        $hasAbility = $token && $token->can('company_panel.*');
        $isWildcard = $token && in_array('*', $token->abilities ?? []);

        if (!$hasRole || !$hasAbility || $isWildcard) {
            Log::warning('Unauthorized company panel access attempt', [
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'ip_address' => $request->ip(),
                'path' => $request->path(),
                'company_panel_role' => $user?->company_panel_role,
                'has_ability' => $hasAbility,
                'is_wildcard' => $isWildcard,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'forbidden',
                'message' => 'Company panel access required.',
            ], 403);
        }

        return $next($request);
    }
}
