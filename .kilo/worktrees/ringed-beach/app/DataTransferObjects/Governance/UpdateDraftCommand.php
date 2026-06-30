<?php

namespace App\DataTransferObjects\Governance;

final class UpdateDraftCommand
{
    public function __construct(
        public readonly string $entityType,
        public readonly int|string $entityId,
        public readonly array $payload,
        public readonly ?int $actorId,
        public readonly string $correlationId,
    ) {
    }
}
