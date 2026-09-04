<?php

declare(strict_types=1);

namespace App\Domain\PropertyWorkspace\Timeline;

use App\Models\EtkiAlaniOlayi;
use App\Models\PropertyWorkspace;
use App\Domain\PropertyWorkspace\Timeline\WorkspaceEvent;
use App\Domain\PropertyWorkspace\PropertyWorkspaceAggregate;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class WorkspaceTimeline
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Event sourcing + CQRS event store for workspace events.
 *
 * This class provides an immutable event store for PropertyWorkspace aggregates.
 * All events are append-only and tenant-isolated.
 *
 * @package App\Domain\PropertyWorkspace\Timeline
 */
class WorkspaceTimeline
{
    /**
     * Aggregate type for workspace events
     */
    private const AGGREGATE_TYPE = 'App\Domain\PropertyWorkspace\PropertyWorkspaceAggregate';

    /**
     * Append a new event to the workspace timeline
     *
     * Events are immutable - this method only appends, never updates or deletes.
     *
     * @param string $workspaceId UUID of the workspace
     * @param WorkspaceEvent $event Event to append
     * @return void
     */
    public function append(string $workspaceId, WorkspaceEvent $event): void
    {
        $workspace = $this->resolveWorkspace($workspaceId);

        if ($workspace === null) {
            throw new \InvalidArgumentException("Workspace not found: {$workspaceId}");
        }

        // Ensure tenant isolation - event tenant must match workspace tenant
        if ($event->tenant_id !== $workspace->tenant_id) {
            throw new \InvalidArgumentException(
                "Tenant mismatch: event tenant_id ({$event->tenant_id}) does not match workspace tenant_id ({$workspace->tenant_id})"
            );
        }

        $sequenceNumber = $this->getNextSequenceNumber($workspaceId, $workspace->tenant_id);

        EtkiAlaniOlayi::create([
            'tenant_id' => $event->tenant_id,
            'aggregate_type' => self::AGGREGATE_TYPE,
            'aggregate_id' => $workspace->id,
            'event_type' => $event->event_type,
            'sequence_number' => $sequenceNumber,
            'payload' => $event->payload,
            'user_id' => auth()->id(),
            'ip_adresi' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get all events for a workspace
     *
     * Tenant isolation is enforced - only events for the specified workspace
     * (which belongs to the authenticated user's tenant) are returned.
     *
     * @param string $workspaceId UUID of the workspace
     * @return Collection<int, EtkiAlaniOlayi>
     */
    public function getEvents(string $workspaceId): Collection
    {
        $workspace = $this->resolveWorkspace($workspaceId);

        if ($workspace === null) {
            return new Collection();
        }

        return EtkiAlaniOlayi::where('aggregate_type', self::AGGREGATE_TYPE)
            ->where('aggregate_id', $workspace->id)
            ->where('tenant_id', $workspace->tenant_id) // Tenant isolation enforced
            ->orderBy('sequence_number')
            ->get();
    }

    /**
     * Replay all events for a workspace to reconstruct current state
     *
     * Returns the reconstructed state array matching PropertyWorkspaceAggregate state structure.
     *
     * @param string $workspaceId UUID of the workspace
     * @return array<string, mixed> Reconstructed state
     */
    public function replay(string $workspaceId): array
    {
        $workspace = $this->resolveWorkspace($workspaceId);

        if ($workspace === null) {
            throw new \InvalidArgumentException("Workspace not found: {$workspaceId}");
        }

        // Initialize state with current DB values
        $state = [
            'workspace_id' => $workspace->workspace_uuid,
            'tenant_id' => $workspace->tenant_id,
            'property_id' => $workspace->property_id,
            'intent' => $workspace->intent,
            'template_id' => $workspace->template_id,
            'state' => $workspace->state,
            'created_at' => $workspace->created_at?->toIso8601String(),
        ];

        // Replay all events in sequence order
        $events = EtkiAlaniOlayi::where('aggregate_type', self::AGGREGATE_TYPE)
            ->where('aggregate_id', $workspace->id)
            ->where('tenant_id', $workspace->tenant_id) // Tenant isolation enforced
            ->orderBy('sequence_number')
            ->get();

        foreach ($events as $event) {
            $state = $this->applyEventToState($event->event_type, $event->payload, $state);
        }

        return $state;
    }

    /**
     * Get the total count of events for a workspace
     *
     * @param string $workspaceId UUID of the workspace
     * @return int Event count
     */
    public function getEventCount(string $workspaceId): int
    {
        $workspace = $this->resolveWorkspace($workspaceId);

        if ($workspace === null) {
            return 0;
        }

        return EtkiAlaniOlayi::where('aggregate_type', self::AGGREGATE_TYPE)
            ->where('aggregate_id', $workspace->id)
            ->where('tenant_id', $workspace->tenant_id) // Tenant isolation enforced
            ->count();
    }

    /**
     * Get the last (most recent) event for a workspace
     *
     * @param string $workspaceId UUID of the workspace
     * @return EtkiAlaniOlayi|null Last event or null if no events
     */
    public function getLastEvent(string $workspaceId): ?EtkiAlaniOlayi
    {
        $workspace = $this->resolveWorkspace($workspaceId);

        if ($workspace === null) {
            return null;
        }

        return EtkiAlaniOlayi::where('aggregate_type', self::AGGREGATE_TYPE)
            ->where('aggregate_id', $workspace->id)
            ->where('tenant_id', $workspace->tenant_id) // Tenant isolation enforced
            ->orderBy('sequence_number', 'desc')
            ->first();
    }

    /**
     * Get all events since a given timestamp
     *
     * @param string $workspaceId UUID of the workspace
     * @param \DateTime $since Timestamp to filter from
     * @return Collection<int, EtkiAlaniOlayi>
     */
    public function getEventsSince(string $workspaceId, \DateTime $since): Collection
    {
        $workspace = $this->resolveWorkspace($workspaceId);

        if ($workspace === null) {
            return new Collection();
        }

        $sinceIso = $since->format('Y-m-d H:i:s');

        return EtkiAlaniOlayi::where('aggregate_type', self::AGGREGATE_TYPE)
            ->where('aggregate_id', $workspace->id)
            ->where('tenant_id', $workspace->tenant_id) // Tenant isolation enforced
            ->where('created_at', '>=', $sinceIso)
            ->orderBy('sequence_number')
            ->get();
    }

    /**
     * Get unique event types that occurred in a workspace
     *
     * @param string $workspaceId UUID of the workspace
     * @return array<int, string> Unique event type names
     */
    public function getEventTypes(string $workspaceId): array
    {
        $workspace = $this->resolveWorkspace($workspaceId);

        if ($workspace === null) {
            return [];
        }

        return EtkiAlaniOlayi::where('aggregate_type', self::AGGREGATE_TYPE)
            ->where('aggregate_id', $workspace->id)
            ->where('tenant_id', $workspace->tenant_id) // Tenant isolation enforced
            ->distinct()
            ->pluck('event_type')
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Check if workspace has any events
     *
     * @param string $workspaceId UUID of the workspace
     * @return bool True if workspace has at least one event
     */
    public function hasEvents(string $workspaceId): bool
    {
        return $this->getEventCount($workspaceId) > 0;
    }

    /**
     * Get event stream for audit purposes
     *
     * Returns a formatted timeline of events with metadata.
     *
     * @param string $workspaceId UUID of the workspace
     * @return array<int, array{
     *     sequence: int,
     *     event_type: string,
     *     occurred_at: string,
     *     payload: array,
     *     user_id: int|null
     * }>
     */
    public function getAuditTrail(string $workspaceId): array
    {
        $events = $this->getEvents($workspaceId);

        return $events->map(function (EtkiAlaniOlayi $event): array {
            return [
                'sequence' => $event->sequence_number,
                'event_type' => $event->event_type,
                'occurred_at' => $event->created_at?->toIso8601String() ?? $event->created_at,
                'payload' => is_string($event->payload) ? json_decode($event->payload, true) : $event->payload,
                'user_id' => $event->user_id,
            ];
        })->all();
    }

    /**
     * Resolve workspace UUID to PropertyWorkspace model
     *
     * Enforces tenant isolation via the authenticated user's tenant.
     *
     * @param string $workspaceId UUID of the workspace
     * @return PropertyWorkspace|null
     */
    private function resolveWorkspace(string $workspaceId): ?PropertyWorkspace
    {
        $tenantId = (int) auth()->user()?->tenant_id ?? 0;

        return PropertyWorkspace::where('workspace_uuid', $workspaceId)
            ->where('tenant_id', $tenantId) // Tenant isolation enforced
            ->first();
    }

    /**
     * Get the next sequence number for a workspace
     *
     * @param string $workspaceId UUID of the workspace
     * @param int $tenantId Tenant ID for additional safety
     * @return int Next sequence number
     */
    private function getNextSequenceNumber(string $workspaceId, int $tenantId): int
    {
        $lastEvent = EtkiAlaniOlayi::where('aggregate_type', self::AGGREGATE_TYPE)
            ->where('tenant_id', $tenantId)
            ->where('payload', 'like', "%\"workspace_id\":\"{$workspaceId}\"%")
            ->orderBy('sequence_number', 'desc')
            ->first();

        if ($lastEvent === null) {
            $workspace = $this->resolveWorkspace($workspaceId);
            if ($workspace === null) {
                return 1;
            }
            // Check by aggregate_id as well
            $lastEvent = EtkiAlaniOlayi::where('aggregate_type', self::AGGREGATE_TYPE)
                ->where('aggregate_id', $workspace->id)
                ->where('tenant_id', $tenantId)
                ->orderBy('sequence_number', 'desc')
                ->first();
        }

        return $lastEvent ? $lastEvent->sequence_number + 1 : 1;
    }

    /**
     * Apply an event to state during replay
     *
     * @param string $eventType Event type name
     * @param mixed $payload Event payload
     * @param array<string, mixed> $state Current state
     * @return array<string, mixed> Updated state
     */
    private function applyEventToState(string $eventType, mixed $payload, array $state): array
    {
        $payloadArray = is_string($payload) ? json_decode($payload, true) : $payload;

        if (!is_array($payloadArray)) {
            return $state;
        }

        return match ($eventType) {
            'WorkspaceCreated' => $this->applyWorkspaceCreated($payloadArray, $state),
            'WorkspaceInitiated' => $this->applyWorkspaceCreated($payloadArray, $state),
            'IntentSelected' => $this->applyIntentSelected($payloadArray, $state),
            'TemplateApplied' => $this->applyTemplateApplied($payloadArray, $state),
            'StateChanged', 'StateTransition' => $this->applyStateChanged($payloadArray, $state),
            'CapabilityExecuted' => $this->applyCapabilityExecuted($payloadArray, $state),
            'CapabilityFailed' => $this->applyCapabilityFailed($payloadArray, $state),
            default => $state,
        };
    }

    /**
     * Apply WorkspaceCreated or WorkspaceInitiated event
     */
    private function applyWorkspaceCreated(array $payload, array $state): array
    {
        $state['workspace_id'] = $payload['workspace_id'] ?? $state['workspace_id'];
        $state['tenant_id'] = (int) ($payload['tenant_id'] ?? $state['tenant_id']);
        $state['ilan_id'] = (int) ($payload['ilan_id'] ?? $state['ilan_id']);
        $state['intent'] = $payload['intent'] ?? $state['intent'];
        $state['template_id'] = $payload['template_id'] ?? $state['template_id'];
        $state['state'] = PropertyWorkspaceAggregate::STATE_WORKSPACE_CREATED;
        $state['created_at'] = $payload['timestamp'] ?? $state['created_at'];

        return $state;
    }

    /**
     * Apply IntentSelected event
     */
    private function applyIntentSelected(array $payload, array $state): array
    {
        $state['intent'] = $payload['intent'] ?? $state['intent'];

        return $state;
    }

    /**
     * Apply TemplateApplied event
     */
    private function applyTemplateApplied(array $payload, array $state): array
    {
        $state['template_id'] = $payload['template_id'] ?? $state['template_id'];

        return $state;
    }

    /**
     * Apply StateChanged or StateTransition event
     */
    private function applyStateChanged(array $payload, array $state): array
    {
        $state['state'] = $payload['to_state'] ?? $state['state'];

        return $state;
    }

    /**
     * Apply CapabilityExecuted event
     */
    private function applyCapabilityExecuted(array $payload, array $state): array
    {
        if (!isset($state['capabilities'])) {
            $state['capabilities'] = [];
        }

        $state['capabilities'][] = [
            'capability' => $payload['capability'] ?? 'unknown',
            'timestamp' => $payload['timestamp'] ?? null,
            'result' => $payload['result'] ?? null,
        ];

        return $state;
    }

    /**
     * Apply CapabilityFailed event
     */
    private function applyCapabilityFailed(array $payload, array $state): array
    {
        if (!isset($state['capabilities'])) {
            $state['capabilities'] = [];
        }

        $state['capabilities'][] = [
            'capability' => $payload['capability'] ?? 'unknown',
            'timestamp' => $payload['timestamp'] ?? null,
            'error' => $payload['error'] ?? null,
            'failed' => true,
        ];

        return $state;
    }
}
