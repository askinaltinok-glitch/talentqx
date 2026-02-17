<?php

namespace App\Models\Traits;

use App\Models\Company;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the trait - automatically apply tenant scope and set tenant_id on create.
     */
    protected static function bootBelongsToTenant(): void
    {
        // Apply global scope for automatic filtering
        static::addGlobalScope(new TenantScope());

        // Automatically set company_id when creating (skip in console/seeder context)
        static::creating(function ($model) {
            // Skip auto-assignment in console context or when bypassed
            if (app()->runningInConsole() && !app()->runningUnitTests()) {
                return;
            }

            if (TenantScope::isBypassed()) {
                return;
            }

            if (!$model->company_id && app()->bound('current_tenant_id')) {
                $model->company_id = app('current_tenant_id');
            }
        });
    }

    /**
     * Get the tenant (company) that owns this model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Scope to query without tenant filter (for admin operations).
     */
    public function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Scope to query for a specific tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
                     ->where('company_id', $tenantId);
    }
}
