<?php

declare(strict_types=1);

namespace App\Services\SaaS;

use App\Models\SaaS\Tenant;
use App\Exceptions\Tenant\TenantNotFoundException;

/**
 * TenantWebhookResolver
 *
 * Resolves the tenant from Meta id, direct tenant_id or uuid.
 */
class TenantWebhookResolver
{
    /**
     * Resolve tenant from phone_number_id (Meta business_id) or tenant_id.
     *
     * @param string $metaId
     * @return Tenant
     * @throws TenantNotFoundException
     */
    public function resolveFromMetaId(string $metaId): Tenant
    {
        // 1. Direct tenant_id (if numeric)
        if (is_numeric($metaId)) {
            $tenant = Tenant::find((int) $metaId);
            if ($tenant) {
                return $tenant;
            }
        }

        // 2. Lookup by uuid, domain or name
        $tenant = Tenant::where('uuid', $metaId)
            ->orWhere('domain', $metaId)
            ->orWhere('name', $metaId)
            ->first();

        if ($tenant) {
            return $tenant;
        }

        throw new TenantNotFoundException("No tenant found matching identifier: {$metaId}");
    }
}
