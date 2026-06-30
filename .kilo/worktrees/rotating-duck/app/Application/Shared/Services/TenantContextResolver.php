<?php

namespace App\Application\Shared\Services;

use App\Application\Shared\DTOs\TenantContext;
use App\Application\Shared\Exceptions\TenantContextMissingException;
use Illuminate\Support\Str;

/**
 * 🔍 TenantContextResolver
 * Resolves the tenant context from the current authentication state.
 */
class TenantContextResolver
{
    /**
     * Resolve the current tenant context.
     * 
     * @throws TenantContextMissingException
     */
    public function resolve(): TenantContext
    {
        $user = $this->getCurrentUser();

        // 🛡️ SAAB Rule: AI calls MUST have a tenant context.
        if (!$user || !isset($user->tenant_id)) {
            throw new TenantContextMissingException();
        }

        return new TenantContext(
            tenantId: (int) $user->tenant_id,
            userId: (int) $user->id,
            requestId: (string) Str::uuid()
        );
    }

    /**
     * Get the current authenticated user.
     * Can be overridden in tests.
     */
    protected function getCurrentUser()
    {
        return auth()->user();
    }

    /**
     * Get the resolved Tenant model.
     */
    public function getTenant(): \App\Models\SaaS\Tenant
    {
        $user = $this->getCurrentUser();
        
        if (!$user || !$user->tenant) {
            throw new TenantContextMissingException("Aktif kullanıcı için kiracı bulunamadı.");
        }

        return $user->tenant;
    }
}
