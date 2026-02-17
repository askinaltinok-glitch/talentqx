<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Routes that are allowed even when password change is required.
     */
    protected array $allowedRoutes = [
        'api/v1/change-password',
        'api/v1/logout',
        'api/v1/user', // Allow checking user status
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // No user = not authenticated, let other middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Check if user must change password
        if ($user->mustChangePassword()) {
            // Allow certain routes
            $currentPath = $request->path();

            foreach ($this->allowedRoutes as $route) {
                if ($currentPath === $route || str_starts_with($currentPath, $route)) {
                    return $next($request);
                }
            }

            // Block all other authenticated requests
            return response()->json([
                'message' => 'Devam etmeden önce şifrenizi değiştirmeniz gerekmektedir.',
                'error' => 'password_change_required',
                'must_change_password' => true,
            ], 403);
        }

        return $next($request);
    }
}
