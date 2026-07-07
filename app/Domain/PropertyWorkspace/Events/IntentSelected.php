<?php

declare(strict_types=1);

namespace App\Domain\PropertyWorkspace\Events;

use JsonSerializable;

/**
 * Class IntentSelected
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Event fired when workspace intent is selected/changed.
 *
 * @package App\Domain\PropertyWorkspace\Events
 */
final class IntentSelected implements JsonSerializable
{
    public function __construct(
        public readonly string $workspaceId,
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
            intent: $data['intent'],
            timestamp: $data['timestamp'],
        );
    }
}
