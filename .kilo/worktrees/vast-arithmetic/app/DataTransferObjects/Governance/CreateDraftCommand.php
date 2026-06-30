<?php

namespace App\DataTransferObjects\Governance;

final class CreateDraftCommand
{
    public function __construct(
        public readonly string $entityType,
        public readonly array $payload,
        public readonly ?int $actorId,
        public readonly string $correlationId,
    ) {
    }
}
