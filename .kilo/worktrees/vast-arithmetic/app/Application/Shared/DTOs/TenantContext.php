<?php

namespace App\Application\Shared\DTOs;

/**
 * 🛂 TENANT CONTEXT DTO
 * The mandatory passport for all AI operations in a SaaS environment.
 */
final readonly class TenantContext
{
    public function __construct(
        public int $tenantId,
        public int $userId,
        public string $requestId,
    ) {}
}
