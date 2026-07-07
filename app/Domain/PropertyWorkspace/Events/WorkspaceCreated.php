<?php

declare(strict_types=1);

namespace App\Domain\PropertyWorkspace\Events;

use JsonSerializable;

/**
 * Class WorkspaceCreated
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Event fired when a new property workspace is created.
 *
 * @package App\Domain\PropertyWorkspace\Events
 */
final class WorkspaceCreated implements JsonSerializable
{
    public function __construct(
        public readonly string $workspaceId,
        public readonly int $tenantId,
        public readonly int $ilanId,
        public readonly string $intent,
        public readonly string $timestamp,
    ) {}

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'workspace_id' => $this->workspaceId,
            'tenant_id' => $this->tenantId,
            'ilan_id' => $this->ilanId,
            'intent' => $this->intent,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * Create from array payload
     */
    public static function fromArray(array $data): self
    {
        return new self(
            workspaceId: $data['workspace_id'],
            tenantId: (int) $data['tenant_id'],
            ilanId: (int) $data['ilan_id'],
            intent: $data['intent'],
            timestamp: $data['timestamp'],
        );
    }
}
