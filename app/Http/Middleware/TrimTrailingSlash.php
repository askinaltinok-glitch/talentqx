<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrimTrailingSlash
{
    /**
     * Handle an incoming request.
     *
     * Removes trailing slash from the request URI to prevent 405 errors
     * when the frontend accidentally adds a trailing slash.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $uri = $request->getRequestUri();

        // Remove trailing slash if present (but not for root path)
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $trimmedUri = rtrim($uri, '/');

            // For non-GET requests, we can't redirect (would lose POST data)
            // Instead, modify the request URI directly
            $request->server->set('REQUEST_URI', $trimmedUri);

            // Also update the path info
            $pathInfo = $request->getPathInfo();
            if ($pathInfo !== '/' && str_ends_with($pathInfo, '/')) {
                $request->server->set('PATH_INFO', rtrim($pathInfo, '/'));
            }
        }

        return $next($request);
    }
}
