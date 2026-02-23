<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiPerformanceLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $durationMs = round((microtime(true) - $start) * 1000, 1);
        $route = $request->route()?->getName() ?? $request->path();

        // Log slow requests (>500ms) at warning level, others at debug
        $level = $durationMs > 500 ? 'warning' : 'debug';

        Log::channel('daily')->$level('api.perf', [
            'route' => $route,
            'method' => $request->method(),
            'duration_ms' => $durationMs,
            'status' => $response->getStatusCode(),
        ]);

        // Add timing header
        $response->headers->set('X-Response-Time', $durationMs . 'ms');

        return $response;
    }
}
