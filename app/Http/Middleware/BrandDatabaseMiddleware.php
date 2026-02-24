<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class BrandDatabaseMiddleware
{
    /**
     * Switch the default database connection and cache prefix based on the X-Brand-Key header.
     *
     * - "talentqx" → mysql_talentqx (talentqx_hr database), cache prefix "talentqx_hr_cache_"
     * - "octopus" or missing → mysql (default, talentqx database), cache prefix "talentqx_octopus_cache_"
     */
    public function handle(Request $request, Closure $next): Response
    {
        $brandKey = strtolower($request->header('X-Brand-Key', 'octopus'));

        if ($brandKey === 'talentqx') {
            DB::setDefaultConnection('mysql_talentqx');
            $cachePrefix = 'talentqx_hr_cache_';
        } else {
            $brandKey = 'octopus';
            DB::setDefaultConnection('mysql');
            $cachePrefix = 'talentqx_octopus_cache_';
        }

        app()->instance('current_brand', $brandKey);

        // Set brand-specific cache prefix to prevent key collisions
        config(['cache.prefix' => $cachePrefix]);
        Cache::forgetDriver(config('cache.default'));

        return $next($request);
    }
}
