<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssessmentRateLimiter
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestKey($request);

        // Different limits for different actions
        $limits = $this->getLimits($request);

        if ($this->limiter->tooManyAttempts($key, $limits['max_attempts'])) {
            $retryAfter = $this->limiter->availableIn($key);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'rate_limit_exceeded',
                    'message' => 'Çok fazla istek gönderildi. Lütfen bekleyin.',
                    'retry_after_seconds' => $retryAfter,
                ],
            ], 429)->withHeaders([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $limits['max_attempts'],
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $this->limiter->hit($key, $limits['decay_seconds']);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $limits['max_attempts'],
            'X-RateLimit-Remaining' => max(0, $limits['max_attempts'] - $this->limiter->attempts($key)),
        ]);
    }

    /**
     * Resolve the request key for rate limiting
     */
    protected function resolveRequestKey(Request $request): string
    {
        // Use combination of IP and token (if available)
        $ip = $request->ip();
        $token = $request->route('token') ?? 'unknown';
        $action = $this->getAction($request);

        return "assessment_rate_limit:{$action}:{$ip}:{$token}";
    }

    /**
     * Get rate limits based on the action
     */
    protected function getLimits(Request $request): array
    {
        $action = $this->getAction($request);

        return match ($action) {
            // Token validation - allow more attempts (checking status)
            'show' => [
                'max_attempts' => 30,
                'decay_seconds' => 60, // 30 requests per minute
            ],
            // Starting assessment - strict limit
            'start' => [
                'max_attempts' => 5,
                'decay_seconds' => 300, // 5 attempts per 5 minutes
            ],
            // Submitting responses - moderate limit
            'response' => [
                'max_attempts' => 60,
                'decay_seconds' => 60, // 60 responses per minute (generous for multi-question)
            ],
            // Completing assessment - strict limit
            'complete' => [
                'max_attempts' => 3,
                'decay_seconds' => 300, // 3 attempts per 5 minutes
            ],
            // Default
            default => [
                'max_attempts' => 20,
                'decay_seconds' => 60,
            ],
        };
    }

    /**
     * Get action from request
     */
    protected function getAction(Request $request): string
    {
        $path = $request->path();

        if (str_ends_with($path, '/start')) {
            return 'start';
        }

        if (str_ends_with($path, '/responses')) {
            return 'response';
        }

        if (str_ends_with($path, '/complete')) {
            return 'complete';
        }

        return 'show';
    }
}
