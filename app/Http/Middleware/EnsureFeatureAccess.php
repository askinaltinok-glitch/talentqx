<?php

namespace App\Http\Middleware;

use App\Models\CandidateMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureAccess
{
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        $candidateId = $request->route('id');

        $tier = 'free';

        if ($candidateId) {
            $membership = CandidateMembership::where('pool_candidate_id', $candidateId)->first();

            if ($membership) {
                $tier = $membership->getEffectiveTier();
            }
        }

        $request->attributes->set('candidate_tier', $tier);

        // If a feature is specified, check access
        if ($feature) {
            $config = config("crew_features.{$feature}");

            if ($config && isset($config[$tier]) && $config[$tier] === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'This feature requires a higher membership tier.',
                    'current_tier' => $tier,
                    'feature' => $feature,
                ], 403);
            }
        }

        return $next($request);
    }
}
