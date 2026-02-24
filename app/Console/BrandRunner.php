<?php

namespace App\Console;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Helper to run a callback against each brand's database.
 *
 * Usage in routes/console.php:
 *   Schedule::call(function () {
 *       BrandRunner::forEachBrand(fn ($brand) => Artisan::call('some:command'));
 *   });
 */
class BrandRunner
{
    /** brand key => [connection, cache_prefix] */
    public const BRANDS = [
        'octopus'  => ['connection' => 'mysql',           'cache_prefix' => 'talentqx_octopus_cache_'],
        'talentqx' => ['connection' => 'mysql_talentqx',  'cache_prefix' => 'talentqx_hr_cache_'],
    ];

    /**
     * Execute a callback once per brand, switching the default DB connection and cache prefix.
     */
    public static function forEachBrand(callable $callback): void
    {
        foreach (self::BRANDS as $brand => $config) {
            DB::setDefaultConnection($config['connection']);
            config(['cache.prefix' => $config['cache_prefix']]);
            Cache::forgetDriver(config('cache.default'));
            app()->instance('current_brand', $brand);

            $callback($brand);
        }

        // Reset to default (octopus)
        DB::setDefaultConnection('mysql');
        config(['cache.prefix' => 'talentqx_octopus_cache_']);
        Cache::forgetDriver(config('cache.default'));
        app()->instance('current_brand', 'octopus');
    }
}
