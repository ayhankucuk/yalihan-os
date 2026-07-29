<?php

namespace App\Domain\ChannelManager\Aggregates;

use App\Domain\CQRS\AggregateRoot;
use App\Domain\ChannelManager\Enums\ChannelManagerEventVocabulary;

/**
 * AvailabilitySyncAggregate — Manages availability date-level sync state
 *
 * Sprint 13 E01: Domain Foundation
 *
 * Responsibilities:
 * - Track per-date availability state per channel
 * - Detect double-booking conflicts
 * - Support idempotent sync operations
 * - Emit availability sync events
 *
 * Domain Invariant:
 * - A date can only be 'available' or 'unavailable' — not both
 * - Conflict detection: same date + different states = conflict
 */
class AvailabilitySyncAggregate extends AggregateRoot
{
    /**
     * Per-date availability state
     *
     * @var array<string, array{available: bool, source: string, synced_at: string}>
     */
    protected array $dateStates = [];

    /**
     * Pending availability updates (not yet synced to channel)
     *
     * @var array<string, array{available: bool, dirty: bool}>
     */
    protected array $pendingUpdates = [];

    protected array $state = [
        'channel_id' => null,
        'property_id' => null,
        'conflict_count' => 0,
        'last_conflict_at' => null,
    ];

    public function __construct(
        int $aggregateId,
        int $tenantId,
        string $channelId,
        int $propertyId,
    ) {
        parent::__construct($aggregateId, $tenantId);

        $this->state['channel_id'] = $channelId;
        $this->state['property_id'] = $propertyId;
    }

    /**
     * Set availability for a date (local update, marks as dirty)
     *
     * @param string $date Y-m-d
     * @param bool $available
     */
    public function setAvailability(string $date, bool $available): void
    {
        $previousState = $this->dateStates[$date] ?? null;

        $this->pendingUpdates[$date] = [
            'available' => $available,
            'dirty' => true,
        ];

        $this->recordEvent(ChannelManagerEventVocabulary::AVAILABILITY_SYNCED->value, [
            'channel_id' => $this->state['channel_id'],
            'property_id' => $this->state['property_id'],
            'date' => $date,
            'available' => $available,
            'previous_state' => $previousState,
            'source' => 'local',
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Receive availability from a remote channel
     *
     * @param string $date Y-m-d
     * @param bool $available
     * @return array|null Conflict details if detected, null otherwise
     */
    public function receiveAvailability(string $date, bool $available): ?array
    {
        $localState = $this->pendingUpdates[$date]['available']
            ?? $this->dateStates[$date]['available']
            ?? null;

        // No conflict if no local state exists
        if ($localState === null) {
            $this->dateStates[$date] = [
                'available' => $available,
                'source' => 'remote',
                'synced_at' => now()->toIso8601String(),
            ];

            $this->recordEvent(ChannelManagerEventVocabulary::AVAILABILITY_PULLED->value, [
                'channel_id' => $this->state['channel_id'],
                'property_id' => $this->state['property_id'],
                'date' => $date,
                'available' => $available,
                'source' => 'remote',
                'synced_at' => now()->toIso8601String(),
            ]);

            return null;
        }

        // Conflict: same date, different states
        if ($localState !== $available) {
            $conflict = [
                'date' => $date,
                'local_state' => $localState,
                'remote_state' => $available,
                'detected_at' => now()->toIso8601String(),
            ];

            $this->recordEvent(ChannelManagerEventVocabulary::AVAILABILITY_CONFLICTED->value, [
                'channel_id' => $this->state['channel_id'],
                'property_id' => $this->state['property_id'],
                'conflict' => $conflict,
                'detected_at' => now()->toIso8601String(),
            ]);

            $this->state['conflict_count']++;
            $this->state['last_conflict_at'] = now()->toIso8601String();

            return $conflict;
        }

        // No conflict: states match, update timestamp
        $this->dateStates[$date] = [
            'available' => $available,
            'source' => 'remote',
            'synced_at' => now()->toIso8601String(),
        ];

        return null;
    }

    /**
     * Mark a date as synced (clear dirty flag)
     *
     * @param string $date Y-m-d
     */
    public function markSynced(string $date): void
    {
        if (isset($this->pendingUpdates[$date])) {
            $this->pendingUpdates[$date]['dirty'] = false;
            $this->dateStates[$date] = [
                'available' => $this->pendingUpdates[$date]['available'],
                'source' => 'local',
                'synced_at' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Get availability state for a date
     */
    public function getDateState(string $date): ?bool
    {
        return $this->pendingUpdates[$date]['available']
            ?? $this->dateStates[$date]['available']
            ?? null;
    }

    /**
     * Get all dirty (unsynced) dates
     *
     * @return array<string, bool>
     */
    public function getDirtyDates(): array
    {
        $dirty = [];
        foreach ($this->pendingUpdates as $date => $update) {
            if ($update['dirty']) {
                $dirty[$date] = $update['available'];
            }
        }
        return $dirty;
    }

    /**
     * Get conflict count
     */
    public function getConflictCount(): int
    {
        return $this->state['conflict_count'];
    }

    /**
     * @inheritDoc
     */
    protected function applyEvent(string $eventType, array $payload): void
    {
        match ($eventType) {
            ChannelManagerEventVocabulary::AVAILABILITY_SYNCED->value,
            ChannelManagerEventVocabulary::AVAILABILITY_PUSHED->value,
            ChannelManagerEventVocabulary::AVAILABILITY_PULLED->value => $this->applyAvailabilityEvent($payload),
            ChannelManagerEventVocabulary::AVAILABILITY_CONFLICTED->value => $this->applyConflictEvent($payload),
            ChannelManagerEventVocabulary::AVAILABILITY_CONFLICT_RESOLVED->value => $this->applyConflictResolvedEvent($payload),
            default => null,
        };
    }

    private function applyAvailabilityEvent(array $payload): void
    {
        $date = $payload['date'];
        $available = $payload['available'];

        $this->dateStates[$date] = [
            'available' => $available,
            'source' => $payload['source'] ?? 'unknown',
            'synced_at' => $payload['synced_at'] ?? now()->toIso8601String(),
        ];

        if (isset($this->pendingUpdates[$date])) {
            $this->pendingUpdates[$date]['dirty'] = false;
        }
    }

    private function applyConflictEvent(array $payload): void
    {
        $this->state['conflict_count']++;
        $this->state['last_conflict_at'] = $payload['detected_at'] ?? now()->toIso8601String();
    }

    private function applyConflictResolvedEvent(array $payload): void
    {
        $resolvedData = $payload['resolved_availability'] ?? [];
        if (isset($resolvedData['date'], $resolvedData['available'])) {
            $this->dateStates[$resolvedData['date']] = [
                'available' => $resolvedData['available'],
                'source' => 'resolution',
                'synced_at' => $payload['resolved_at'] ?? now()->toIso8601String(),
            ];
        }
    }
}
