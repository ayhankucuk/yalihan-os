<?php

declare(strict_types=1);

namespace App\Domain\PropertyWorkspace\Timeline;

use JsonSerializable;

/**
 * Class WorkspaceEvent
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Base class for all workspace events.
 *
 * Events are the immutable records of what happened in a workspace.
 * They are append-only and form the event store for event sourcing.
 *
 * @package App\Domain\PropertyWorkspace\Timeline
 */
abstract class WorkspaceEvent implements JsonSerializable
{
    /**
     * Workspace UUID
     */
    public readonly string $workspace_id;

    /**
     * Tenant ID for isolation
     */
    public readonly int $tenant_id;

    /**
     * ISO8601 timestamp when the event occurred
     */
    public readonly string $occurred_at;

    /**
     * Event type identifier (class name without namespace)
     */
    public readonly string $event_type;

    /**
     * Event payload (event-specific data
     *
     * @var array<string, mixed>
     */
    public array $payload;

    /**
     * Create a new workspace event
     *
     * @param string $workspaceId Workspace UUID
     * @param int $tenantId Tenant ID
     * @param array<string, mixed> $payload Event-specific payload
     */
    public function __construct(
        string $workspaceId,
        int $tenantId,
        array $payload = []
    ) {
        $this->workspace_id = $workspaceId;
        $this->tenant_id = $tenantId;
        $this->occurred_at = now()->toIso8601String();
        $this->event_type = $this->resolveEventType();
        $this->payload = $payload;
    }

    /**
     * Resolve the event type from class name
     *
     * @return string Event type identifier
     */
    private function resolveEventType(): string
    {
        $className = static::class;

        if (str_contains($className, '\\')) {
            $parts = explode('\\', $className);
            $className = end($parts);
        }

        return $className;
    }

    /**
     * Get workspace ID
     *
     * @return string
     */
    public function getWorkspaceId(): string
    {
        return $this->workspace_id;
    }

    /**
     * Get tenant ID
     *
     * @return int
     */
    public function getTenantId(): int
    {
        return $this->tenant_id;
    }

    /**
     * Get event type
     *
     * @return string
     */
    public function getEventType(): string
    {
        return $this->event_type;
    }

    /**
     * Get occurred at timestamp
     *
     * @return string ISO8601 timestamp
     */
    public function getOccurredAt(): string
    {
        return $this->occurred_at;
    }

    /**
     * Get event payload
     *
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * Convert event to array for storage
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'workspace_id' => $this->workspace_id,
            'tenant_id' => $this->tenant_id,
            'occurred_at' => $this->occurred_at,
            'event_type' => $this->event_type,
            'payload' => $this->payload,
        ];
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create event from stored array data
     *
     * @param array<string, mixed> $data Event data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        $event = new static(
            workspaceId: $data['workspace_id'],
            tenantId: (int) ($data['tenant_id'] ?? 0),
            payload: $data['payload'] ?? []
        );

        $event->occurred_at = $data['occurred_at'] ?? now()->toIso8601String();
        $event->event_type = $data['event_type'] ?? static::class;

        return $event;
    }
}
