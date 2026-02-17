<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trim trailing slashes from API requests to prevent 405 errors
        $middleware->prepend(\App\Http\Middleware\TrimTrailingSlash::class);

        // Note: EnsureFrontendRequestsAreStateful removed - using token-based auth only
        // ForceJsonResponse ensures no 302 redirects - always JSON errors
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
            \App\Http\Middleware\TenantMiddleware::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'tenant' => \App\Http\Middleware\TenantMiddleware::class,
            'require.tenant' => \App\Http\Middleware\RequireTenant::class,
            'force.password.change' => \App\Http\Middleware\ForcePasswordChange::class,
            'platform.admin' => \App\Http\Middleware\RequirePlatformAdmin::class,
            'customer.scope' => \App\Http\Middleware\RequireCustomerScope::class,
            'subscription.access' => \App\Http\Middleware\SubscriptionAccessMiddleware::class,
            'api.token' => \App\Http\Middleware\ApiTokenAuth::class,
            'feature.access' => \App\Http\Middleware\EnsureFeatureAccess::class,
            'platform.octopus_admin' => \App\Http\Middleware\RequireOctopusAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
