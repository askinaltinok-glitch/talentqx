<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ForceDefaultDatabase
{
    /**
     * Force the default database connection to 'mysql' (octopus/default DB).
     * Used for internal management routes (company panel) that should always
     * use the main database regardless of X-Brand-Key header.
     */
    public function handle(Request $request, Closure $next): Response
    {
        DB::setDefaultConnection('mysql');

        return $next($request);
    }
}
