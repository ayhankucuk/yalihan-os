<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Resolution\Registry;

use App\Models\PropertyConfigVersion;
use App\Enums\SistemDurumu;
use Illuminate\Support\Collection;

/**
 * Class GlobalSuperRegistry
 *
 * Enterprise-level audit registry for overseeing all tenants.
 */
class GlobalSuperRegistry
{
    public function __construct(
        private readonly TenantConfigRegistry $tenantRegistry
    ) {}

    /**
     * Audit across all tenants for drift or integrity issues.
     */
    public function auditAllTenants(): array
    {
        $tenants = PropertyConfigVersion::select('tenant_id')->distinct()->pluck('tenant_id');
        $reports = [];

        foreach ($tenants as $tenantId) {
            try {
                $version = $this->tenantRegistry->resolve($tenantId);
                $reports[$tenantId] = [
                    'yayin_durumu' => SistemDurumu::HEALTHY->value,
                    'version_hash' => $version->version_hash,
                    'compromised' => $this->tenantRegistry->isSystemCompromised($tenantId)
                ];
            } catch (\Exception $e) {
                $reports[$tenantId] = [
                    'yayin_durumu' => SistemDurumu::CRITICAL->value,
                    'error' => $e->getMessage(),
                    'compromised' => $this->tenantRegistry->isSystemCompromised($tenantId)
                ];
            }
        }

        return $reports;
    }
}
