<?php

namespace App\DataTransferObjects\Governance;

final class GovernanceAuditContext
{
    public function __construct(
        public readonly string $entityType,
        public readonly int|string $entityId,
        public readonly ?int $actorId,
        public readonly string $correlationId,
        public readonly ?string $reason = null,
        public readonly array $payloadSnapshot = [],
    ) {
    }
}
