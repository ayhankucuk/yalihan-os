<?php

namespace App\Domain\CommercialOffering\Exceptions;

/**
 * Exception for cross-tenant or cross-workspace access violations.
 */
class CommercialOfferingAccessException extends \DomainException
{
    public static function crossTenantAccess(int $offeringTenantId, int $currentTenantId): self
    {
        return new self(
            "Cross-tenant access denied. Offering belongs to tenant {$offeringTenantId}, current context is {$currentTenantId}."
        );
    }

    public static function crossWorkspaceAccess(int $offeringWorkspaceId, int $currentWorkspaceId): self
    {
        return new self(
            "Cross-workspace access denied. Offering belongs to workspace {$offeringWorkspaceId}, current context is {$currentWorkspaceId}."
        );
    }

    public static function tenantContextRequired(): self
    {
        return new self('Tenant context is required for CommercialOffering operations.');
    }
}
