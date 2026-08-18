<?php

namespace App\Domain\ChannelManager\Aggregates;

use App\Domain\CQRS\AggregateRoot;
use App\Domain\ChannelManager\Enums\ChannelManagerEventVocabulary;
use App\Domain\ChannelManager\Models\ChannelApiResponse;

/**
 * ChannelManagerAggregate — Manages channel synchronization state
 *
 * Sprint 13 E01: Domain Foundation
 *
 * Responsibilities:
 * - Track channel connections (Airbnb, Booking, Sahibinden...)
 * - Manage availability synchronization state
 * - Record availability sync events
 * - Detect and resolve conflicts
 *
 * Domain Invariants:
 * - All state changes recorded as immutable events
 * - Tenant isolation enforced at aggregate level
 * - Idempotent operations (same input → same output)
 */
class ChannelManagerAggregate extends AggregateRoot
{
    /**
     * Aggregate state (reconstructed from event stream)
     *
     * @var array
     */
    protected array $state = [
        'channel_id' => null,
        'property_id' => null,
        'connection_status' => 'disconnected', // disconnected | connected | error
        'last_sync_at' => null,
        'last_sync_status' => null, // success | failed | conflict
        'pending_syncs' => 0,
    ];

    /**
     * @param int $aggregateId ChannelManagerConfig ID
     * @param int $tenantId
     * @param string $channelId e.g. 'airbnb', 'booking', 'sahibinden'
     * @param int $propertyId Related property ID
     */
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
     * Connect a channel
     */
    public function connectChannel(): void
    {
        if ($this->state['connection_status'] === 'connected') {
            return; // Idempotent: already connected
        }

        $this->recordEvent(ChannelManagerEventVocabulary::CHANNEL_CONNECTED->value, [
            'channel_id' => $this->state['channel_id'],
            'property_id' => $this->state['property_id'],
            'connected_at' => now()->toIso8601String(),
        ]);

        $this->state['connection_status'] = 'connected';
    }

    /**
     * Disconnect a channel
     */
    public function disconnectChannel(): void
    {
        if ($this->state['connection_status'] === 'disconnected') {
            return; // Idempotent: already disconnected
        }

        $this->recordEvent(ChannelManagerEventVocabulary::CHANNEL_DISCONNECTED->value, [
            'channel_id' => $this->state['channel_id'],
            'property_id' => $this->state['property_id'],
            'disconnected_at' => now()->toIso8601String(),
        ]);

        $this->state['connection_status'] = 'disconnected';
    }

    /**
     * Push availability update to channel
     *
     * @param array $availabilityData ['date' => 'Y-m-d', 'available' => bool]
     * @param ChannelApiResponse|null $response API response (null = optimistic success)
     */
    public function pushAvailability(array $availabilityData, ?ChannelApiResponse $response = null): void
    {
        $syncSuccess = $response === null || $response->success;

        $eventType = $syncSuccess
            ? ChannelManagerEventVocabulary::AVAILABILITY_PUSHED->value
            : ChannelManagerEventVocabulary::CHANNEL_SYNC_FAILED->value;

        $payload = [
            'channel_id' => $this->state['channel_id'],
            'property_id' => $this->state['property_id'],
            'availability' => $availabilityData,
            'succeeded' => $syncSuccess,
            'error_message' => $response?->errorMessage,
            'error_code' => $response?->errorCode,
            'synced_at' => now()->toIso8601String(),
        ];

        $this->recordEvent($eventType, $payload);

        $this->state['last_sync_at'] = now()->toIso8601String();
        $this->state['last_sync_status'] = $syncSuccess ? 'success' : 'failed';

        if ($syncSuccess) {
            $this->state['pending_syncs'] = max(0, $this->state['pending_syncs'] - 1);
        }
    }

    /**
     * Pull availability from channel and record
     *
     * @param array $availabilityRecords Array of ['date' => 'Y-m-d', 'available' => bool]
     * @param ChannelApiResponse|null $response
     */
    public function pullAvailability(array $availabilityRecords, ?ChannelApiResponse $response = null): void
    {
        $syncSuccess = $response === null || $response->success;

        $eventType = $syncSuccess
            ? ChannelManagerEventVocabulary::AVAILABILITY_PULLED->value
            : ChannelManagerEventVocabulary::CHANNEL_SYNC_FAILED->value;

        $this->recordEvent($eventType, [
            'channel_id' => $this->state['channel_id'],
            'property_id' => $this->state['property_id'],
            'availability_records' => $availabilityRecords,
            'succeeded' => $syncSuccess,
            'error_message' => $response?->errorMessage,
            'synced_at' => now()->toIso8601String(),
        ]);

        $this->state['last_sync_at'] = now()->toIso8601String();
        $this->state['last_sync_status'] = $syncSuccess ? 'success' : 'failed';
    }

    /**
     * Record a detected availability conflict
     *
     * @param array $conflictDetails ['date' => 'Y-m-d', 'local_state' => bool, 'remote_state' => bool]
     */
    public function recordConflict(array $conflictDetails): void
    {
        $this->recordEvent(ChannelManagerEventVocabulary::AVAILABILITY_CONFLICTED->value, [
            'channel_id' => $this->state['channel_id'],
            'property_id' => $this->state['property_id'],
            'conflict' => $conflictDetails,
            'detected_at' => now()->toIso8601String(),
        ]);

        $this->state['last_sync_status'] = 'conflict';
    }

    /**
     * Resolve a previously recorded conflict
     *
     * @param string $resolution 'local_wins' | 'remote_wins' | 'manual'
     * @param array $resolvedData The resolved availability state
     */
    public function resolveConflict(string $resolution, array $resolvedData): void
    {
        $this->recordEvent(ChannelManagerEventVocabulary::AVAILABILITY_CONFLICT_RESOLVED->value, [
            'channel_id' => $this->state['channel_id'],
            'property_id' => $this->state['property_id'],
            'resolution' => $resolution,
            'resolved_availability' => $resolvedData,
            'resolved_at' => now()->toIso8601String(),
        ]);

        $this->state['last_sync_status'] = 'success';
    }

    /**
     * Start a sync job
     */
    public function startSyncJob(string $syncType): void
    {
        $this->recordEvent(ChannelManagerEventVocabulary::SYNC_JOB_STARTED->value, [
            'channel_id' => $this->state['channel_id'],
            'property_id' => $this->state['property_id'],
            'sync_type' => $syncType, // 'push' | 'pull' | 'full'
            'started_at' => now()->toIso8601String(),
        ]);

        $this->state['pending_syncs']++;
    }

    /**
     * Complete a sync job
     */
    public function completeSyncJob(string $syncType, int $itemsProcessed): void
    {
        $this->recordEvent(ChannelManagerEventVocabulary::SYNC_JOB_COMPLETED->value, [
            'channel_id' => $this->state['channel_id'],
            'property_id' => $this->state['property_id'],
            'sync_type' => $syncType,
            'items_processed' => $itemsProcessed,
            'completed_at' => now()->toIso8601String(),
        ]);

        $this->state['pending_syncs'] = max(0, $this->state['pending_syncs'] - 1);
        $this->state['last_sync_status'] = 'success';
        $this->state['last_sync_at'] = now()->toIso8601String();
    }

    /**
     * Fail a sync job
     */
    public function failSyncJob(string $syncType, string $reason): void
    {
        $this->recordEvent(ChannelManagerEventVocabulary::SYNC_JOB_FAILED->value, [
            'channel_id' => $this->state['channel_id'],
            'property_id' => $this->state['property_id'],
            'sync_type' => $syncType,
            'reason' => $reason,
            'failed_at' => now()->toIso8601String(),
        ]);

        $this->state['pending_syncs'] = max(0, $this->state['pending_syncs'] - 1);
        $this->state['last_sync_status'] = 'failed';
        $this->state['last_sync_at'] = now()->toIso8601String();
    }

    /**
     * Get current aggregate state
     */
    public function getState(): array
    {
        return $this->state;
    }

    /**
     * Check if channel is connected
     */
    public function isConnected(): bool
    {
        return $this->state['connection_status'] === 'connected';
    }

    /**
     * Check if there are pending syncs
     */
    public function hasPendingSyncs(): bool
    {
        return $this->state['pending_syncs'] > 0;
    }

    // ─── Event Application ───────────────────────────────────────────────

    /**
     * @inheritDoc
     */
    protected function applyEvent(string $eventType, array $payload): void
    {
        match ($eventType) {
            ChannelManagerEventVocabulary::CHANNEL_CONNECTED->value,
            ChannelManagerEventVocabulary::CHANNEL_DISCONNECTED->value => $this->applyChannelEvent($eventType),
            ChannelManagerEventVocabulary::AVAILABILITY_PUSHED->value,
            ChannelManagerEventVocabulary::AVAILABILITY_PULLED->value => $this->applySyncEvent($payload),
            ChannelManagerEventVocabulary::AVAILABILITY_CONFLICTED->value => $this->state['last_sync_status'] = 'conflict',
            ChannelManagerEventVocabulary::AVAILABILITY_CONFLICT_RESOLVED->value => $this->state['last_sync_status'] = 'success',
            ChannelManagerEventVocabulary::SYNC_JOB_STARTED->value => $this->state['pending_syncs']++,
            ChannelManagerEventVocabulary::SYNC_JOB_COMPLETED->value => $this->applySyncEvent($payload),
            ChannelManagerEventVocabulary::SYNC_JOB_FAILED->value => $this->applySyncEvent($payload),
            default => null,
        };
    }

    private function applyChannelEvent(string $eventType): void
    {
        $this->state['connection_status'] = match ($eventType) {
            ChannelManagerEventVocabulary::CHANNEL_CONNECTED->value => 'connected',
            ChannelManagerEventVocabulary::CHANNEL_DISCONNECTED->value => 'disconnected',
            default => $this->state['connection_status'],
        };
    }

    private function applySyncEvent(array $payload): void
    {
        $this->state['last_sync_at'] = $payload['synced_at']
            ?? $payload['completed_at']
            ?? $payload['failed_at']
            ?? now()->toIso8601String();

        $this->state['last_sync_status'] = ($payload['succeeded'] ?? true) ? 'success' : 'failed';
        $this->state['pending_syncs'] = max(0, $this->state['pending_syncs'] - 1);
    }
}
