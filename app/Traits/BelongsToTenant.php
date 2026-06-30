<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use App\Services\SaaS\TenantContextService;

trait BelongsToTenant
{
    /**
     * Boot the belongs to tenant trait for a model.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            $tenantService = app(TenantContextService::class);
            if ($tenantService->hasTenant() && empty($model->tenant_id)) {
                $model->tenant_id = $tenantService->getTenant()->id;
            }
        });
    }

    /**
     * Scope a query to ignore tenant scoping.
     */
    public function scopeWithoutTenant($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Scope a query to filter by specific tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId);
    }
}
