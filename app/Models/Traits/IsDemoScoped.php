<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * IsDemoScoped — Global scope to exclude demo records from production queries.
 *
 * Default: is_demo=false (production only).
 * Bypass via request params: include_demo=1 (all), only_demo=1 (demo only).
 * Bypass in code: ->withoutGlobalScope('exclude_demo') or ->includeDemo() / ->onlyDemo()
 */
trait IsDemoScoped
{
    public static function bootIsDemoScoped(): void
    {
        static::addGlobalScope('exclude_demo', function (Builder $query) {
            $table = $query->getModel()->getTable();

            // In console: always production only (ML learn, cron, etc.)
            if (app()->runningInConsole()) {
                $query->where("{$table}.is_demo", false);
                return;
            }

            $request = request();

            // Admin bypass: include_demo=1 → no filter (show all)
            if ($request && $request->boolean('include_demo')) {
                return;
            }

            // Admin bypass: only_demo=1 → show only demo
            if ($request && $request->boolean('only_demo')) {
                $query->where("{$table}.is_demo", true);
                return;
            }

            // Default: production only
            $query->where("{$table}.is_demo", false);
        });
    }

    /**
     * Include both production and demo records.
     */
    public function scopeIncludeDemo($query)
    {
        return $query->withoutGlobalScope('exclude_demo');
    }

    /**
     * Show only demo records.
     */
    public function scopeOnlyDemo($query)
    {
        return $query->withoutGlobalScope('exclude_demo')
            ->where($this->getTable() . '.is_demo', true);
    }

    /**
     * Explicitly filter to production only (useful after withoutGlobalScope).
     */
    public function scopeProductionOnly($query)
    {
        return $query->withoutGlobalScope('exclude_demo')
            ->where($this->getTable() . '.is_demo', false);
    }
}
