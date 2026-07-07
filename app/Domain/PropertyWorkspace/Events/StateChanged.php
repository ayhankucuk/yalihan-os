<?php

declare(strict_types=1);

namespace App\Domain\PropertyWorkspace\Events;

use JsonSerializable;

/**
 * Class StateChanged
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Event fired when workspace state transitions.
 *
 * @package App\Domain\PropertyWorkspace\Events
 */
final class StateChanged implements JsonSerializable
{
    public function __construct(
        public readonly string $workspaceId,
        public readonly string $fromState,
        public readonly string $toState,
        public readonly string $timestamp,
    ) {}

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'workspace_id' => $this->workspaceId,
            'from_state' => $this->fromState,
            'to_state' => $this->toState,
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
            fromState: $data['from_state'],
            toState: $data['to_state'],
            timestamp: $data['timestamp'],
        );
    }
}
