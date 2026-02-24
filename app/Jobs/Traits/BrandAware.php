<?php

namespace App\Jobs\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Makes a queue job brand-aware.
 *
 * Captures the current brand key at dispatch time and restores the
 * correct database connection and cache prefix when the job is picked
 * up by a worker.
 *
 * Usage:
 *   use BrandAware;
 *   // In handle(): $this->setBrandDatabase();
 */
trait BrandAware
{
    public string $brandKey = 'octopus';

    /**
     * Call from the job constructor (or initializer) to capture the current brand.
     */
    protected function captureBrand(): void
    {
        $this->brandKey = app()->bound('current_brand')
            ? app('current_brand')
            : 'octopus';
    }

    /**
     * Call at the start of handle() to restore the correct database connection and cache prefix.
     */
    protected function setBrandDatabase(): void
    {
        $connection = $this->brandKey === 'talentqx' ? 'mysql_talentqx' : 'mysql';
        $cachePrefix = $this->brandKey === 'talentqx' ? 'talentqx_hr_cache_' : 'talentqx_octopus_cache_';

        DB::setDefaultConnection($connection);
        config(['cache.prefix' => $cachePrefix]);
        Cache::forgetDriver(config('cache.default'));
        app()->instance('current_brand', $this->brandKey);
    }
}
