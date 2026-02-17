<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Contexts where tenant scope should be bypassed.
     */
    protected static bool $bypassed = false;

    /**
     * Apply the scope to a given Eloquent query builder.
     * Automatically filters all queries by current tenant (company_id).
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Skip if globally bypassed
        if (self::$bypassed) {
            return;
        }

        // Skip in console/artisan context (seeds, commands)
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }

        // Skip if no tenant context is set
        if (!app()->bound('current_tenant_id')) {
            return;
        }

        $tenantId = app('current_tenant_id');

        if ($tenantId) {
            $builder->where($model->getTable() . '.company_id', $tenantId);
        }
    }

    /**
     * Temporarily bypass tenant scope for admin operations.
     */
    public static function bypass(callable $callback): mixed
    {
        self::$bypassed = true;
        try {
            return $callback();
        } finally {
            self::$bypassed = false;
        }
    }

    /**
     * Check if scope is currently bypassed.
     */
    public static function isBypassed(): bool
    {
        return self::$bypassed;
    }

    /**
     * Manually enable bypass mode.
     */
    public static function enableBypass(): void
    {
        self::$bypassed = true;
    }

    /**
     * Manually disable bypass mode.
     */
    public static function disableBypass(): void
    {
        self::$bypassed = false;
    }
}
