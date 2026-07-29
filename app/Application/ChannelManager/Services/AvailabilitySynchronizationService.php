<?php

namespace App\Application\ChannelManager\Services;

use App\Application\ChannelManager\DTOs\SynchronizeAvailabilityCommand;
use App\Application\ChannelManager\Exceptions\AvailabilityConflictException;
use App\Application\ChannelManager\Exceptions\ChannelSynchronizationException;
use App\Domain\ChannelManager\Contracts\AvailabilitySynchronizer;
use App\Domain\ChannelManager\Contracts\ChannelAdapter;
use App\Domain\ChannelManager\Enums\ChannelManagerEventVocabulary;
use App\Domain\ChannelManager\Models\ChannelApiResponse;
use App\Domain\ChannelManager\Models\SyncResult;
use App\Jobs\ChannelManager\SynchronizeAvailabilityJob;
use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Models\ChannelSyncExecution;
use App\Models\PropertyAvailability;
use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AvailabilitySynchronizationService — Canonical availability sync engine
 *
 * Sprint 13 E02: Availability Synchronization
 *
 * Responsibilities:
 * 1. Canonical availability FIRST — update local state before external sync
 * 2. Queue-first execution — DB transaction does NOT call external APIs
 * 3. Idempotent operations — same command = same result
 * 4. Conflict detection — detect and surface conflicts without silent overwrite
 * 5. Replay safety — replay creates new execution, doesn't mutate original
 * 6. Tenant isolation — all operations are tenant-scoped
 *
 * Operation flow:
 *   Canonical Reservation confirmed
 *       ↓
 *   Canonical Availability updated (in DB)
 *       ↓
 *   Synchronization execution created (immutable event)
 *       ↓
 *   afterCommit → SynchronizeAvailabilityJob dispatched
 *       ↓
 *   Registered channels updated
 *       ↓
 *   Result recorded in aggregate
 */
class AvailabilitySynchronizationService
{
    public function __construct(
        private readonly AvailabilitySynchronizer $synchronizer,
    ) {}

    /**
     * Synchronize availability for a reservation event
     *
     * This is the canonical entry point for availability sync.
     * Called AFTER the DB transaction commits (e.g., from a ReservationService).
     *
     * @param SynchronizeAvailabilityCommand $command
     * @param int $userId Who triggered this sync (for audit)
     * @return SyncResult
     */
    public function synchronize(SynchronizeAvailabilityCommand $command, int $userId): SyncResult
    {
        $command->validate();
        $this->enforceTenantIsolation($command->tenantId, $command->propertyId);

        $idempotencyKey = $command->getIdempotencyKey();
        $correlationId = $command->correlationId ?? $this->generateCorrelationId();

        // ─── Step 1: Check idempotency ───────────────────────────────
        $existingSync = $this->findExistingSync($idempotencyKey, $command->tenantId);
        if ($existingSync !== null) {
            Log::info('AvailabilitySync: Idempotent hit', [
                'idempotency_key' => $idempotencyKey,
                'existing_sync_id' => $existingSync->id,
            ]);
            return $this->buildResultFromExistingSync($existingSync);
        }

        // ─── Step 2: Update canonical availability (local DB first) ────
        $conflictDates = [];
        $blockedDates = [];

        DB::transaction(function () use ($command, &$conflictDates, &$blockedDates) {
            foreach ($command->getDates() as $date) {
                $existing = PropertyAvailability::where('property_id', $command->propertyId)
                    ->where('date', $date)
                    ->where('tenant_id', $command->tenantId)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null && $existing->is_available === false && $command->available === false) {
                    // Already blocked by another reservation — idempotent skip
                    if ($existing->reservation_id !== $command->reservationId) {
                        $conflictDates[$date] = [
                            'existing_block' => [
                                'reservation_id' => $existing->reservation_id,
                                'block_reason' => $existing->block_reason,
                            ],
                        ];
                        continue;
                    }
                }

                // Update or create availability record
                PropertyAvailability::updateOrCreate(
                    [
                        'property_id' => $command->propertyId,
                        'date' => $date,
                        'tenant_id' => $command->tenantId,
                    ],
                    [
                        'is_available' => $command->available,
                        'block_reason' => $command->isBlocking() ? $command->blockReason : null,
                        'source_system' => 'canonical',
                        'reservation_id' => $command->isBlocking() ? $command->reservationId : null,
                    ]
                );

                $blockedDates[] = $date;
            }
        });

        // ─── Step 3: Record sync execution (immutable event) ──────────
        $syncRecord = $this->recordSyncExecution(
            $command,
            $idempotencyKey,
            $correlationId,
            $blockedDates,
            $conflictDates
        );

        // ─── Step 4: Dispatch queue job (afterCommit) ─────────────────
        SynchronizeAvailabilityJob::dispatch($syncRecord->id)
            ->afterCommit();

        return SyncResult::success(
            syncedCount: count($blockedDates),
            conflicts: $conflictDates,
            metadata: [
                'sync_record_id' => $syncRecord->id,
                'correlation_id' => $correlationId,
                'idempotency_key' => $idempotencyKey,
            ]
        );
    }

    /**
     * Process a queued sync execution (called by SynchronizeAvailabilityJob)
     *
     * @param int $syncRecordId
     * @return SyncResult
     */
    public function processQueuedSync(int $syncRecordId): SyncResult
    {
        $syncRecord = ChannelSyncExecution::findOrFail($syncRecordId);

        // Replay safety: check if already processed
        if ($syncRecord->processed_at !== null) {
            return $this->buildResultFromExistingSync($syncRecord);
        }

        $command = $this->buildCommandFromSyncRecord($syncRecord);
        $registeredChannels = $this->getRegisteredChannels($command->propertyId, $command->tenantId);

        $totalSynced = 0;
        $allConflicts = [];

        foreach ($registeredChannels as $channelAdapter) {
            $result = $this->syncToChannel($channelAdapter, $command);

            if ($result->hasConflicts()) {
                $allConflicts = array_merge($allConflicts, $result->conflicts);
            }

            $totalSynced += $result->syncedCount;
        }

        // Record completion
        $syncRecord->markProcessed($totalSynced, $allConflicts);

        return SyncResult::success(
            syncedCount: $totalSynced,
            conflicts: $allConflicts,
            metadata: [
                'sync_record_id' => $syncRecord->id,
                'channels_count' => count($registeredChannels),
            ]
        );
    }

    /**
     * Detect conflicts for a date range (for pull-based sync)
     */
    public function detectConflicts(
        int $tenantId,
        int $propertyId,
        string $channelId,
        string $fromDate,
        string $toDate
    ): array {
        $this->enforceTenantIsolation($tenantId, $propertyId);

        $localAvailability = PropertyAvailability::where('property_id', $propertyId)
            ->whereBetween('date', [$fromDate, $toDate])
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy(fn($r) => $r->date);

        $conflicts = [];
        $current = Carbon::parse($fromDate);
        $end = Carbon::parse($toDate);

        while ($current->lte($end)) {
            $date = $current->format('Y-m-d');
            $local = $localAvailability->get($date);

            // For now, return empty conflicts (pull logic implemented in E03)
            // E02 only handles push-based sync
            $current->addDay();
        }

        return $conflicts;
    }

    /**
     * Resolve a detected conflict
     */
    public function resolveConflict(
        int $tenantId,
        int $propertyId,
        string $channelId,
        string $date,
        string $resolution
    ): SyncResult {
        $this->enforceTenantIsolation($tenantId, $propertyId);

        if (!in_array($resolution, ['local_wins', 'remote_wins', 'manual'])) {
            throw new \InvalidArgumentException("resolution must be one of: local_wins, remote_wins, manual");
        }

        $localRecord = PropertyAvailability::where('property_id', $propertyId)
            ->where('date', $date)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $resolvedState = match ($resolution) {
            'local_wins' => $localRecord->is_available,
            'remote_wins' => !$localRecord->is_available,
            'manual' => $localRecord->is_available, // Keep local, require manual intervention
        };

        DB::transaction(function () use ($tenantId, $propertyId, $date, $resolvedState, $localRecord) {
            PropertyAvailability::where('property_id', $propertyId)
                ->where('date', $date)
                ->where('tenant_id', $tenantId)
                ->update([
                    'is_available' => $resolvedState,
                    'source_system' => 'conflict_resolution',
                ]);

            $localRecord->update(['source_system' => 'conflict_resolution_resolved']);
        });

        return SyncResult::success(1, [], [
            'resolution' => $resolution,
            'resolved_state' => $resolvedState ? 'available' : 'blocked',
        ]);
    }

    // ─── Private helpers ────────────────────────────────────────────────

    private function enforceTenantIsolation(int $tenantId, int $propertyId): void
    {
        $property = Ilan::where('id', $propertyId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($property === null) {
            throw new \RuntimeException("Property {$propertyId} not found for tenant {$tenantId}");
        }
    }

    private function findExistingSync(string $idempotencyKey, int $tenantId): ?ChannelSyncExecution
    {
        return ChannelSyncExecution::where('idempotency_key', $idempotencyKey)
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->first();
    }

    private function buildResultFromExistingSync(ChannelSyncExecution $sync): SyncResult
    {
        $conflicts = $sync->conflicts ?? [];
        return SyncResult::success(
            syncedCount: $sync->synced_count ?? 0,
            conflicts: is_array($conflicts) ? $conflicts : [],
            metadata: [
                'sync_record_id' => $sync->id,
                'idempotent' => true,
                'original_created_at' => $sync->created_at->toIso8601String(),
            ]
        );
    }

    private function recordSyncExecution(
        SynchronizeAvailabilityCommand $command,
        string $idempotencyKey,
        string $correlationId,
        array $blockedDates,
        array $conflictDates
    ): ChannelSyncExecution {
        return ChannelSyncExecution::create([
            'tenant_id' => $command->tenantId,
            'property_id' => $command->propertyId,
            'reservation_id' => $command->reservationId,
            'operation' => $command->operation,
            'block_reason' => $command->blockReason,
            'date_range_start' => $command->dateRange['start'],
            'date_range_end' => $command->dateRange['end'],
            'target_availability' => $command->available,
            'synced_dates' => $blockedDates,
            'conflicts' => $conflictDates,
            'idempotency_key' => $idempotencyKey,
            'correlation_id' => $correlationId,
            'status' => 'dispatched',
        ]);
    }

    private function generateCorrelationId(): string
    {
        return sprintf(
            'sync-%s-%s',
            now()->format('Ymd'),
            \Illuminate\Support\Str::random(12)
        );
    }

    /**
     * @return array<ChannelAdapter>
     */
    private function getRegisteredChannels(int $propertyId, int $tenantId): array
    {
        $channelSyncs = IlanTakvimSync::where('ilan_id', $propertyId)
            ->where('is_sync_active', true)
            ->where('senkron_durumu', 'active')
            ->whereHas('ilan', fn($q) => $q->where('tenant_id', $tenantId))
            ->get();

        // In E02, we return the InMemory adapter for testing
        // Real adapters are registered in E03
        $adapters = [];
        foreach ($channelSyncs as $sync) {
            $adapter = $this->resolveChannelAdapter($sync->platform);
            if ($adapter !== null) {
                $adapters[] = $adapter;
            }
        }

        return $adapters;
    }

    private function resolveChannelAdapter(string $platform): ?ChannelAdapter
    {
        return match ($platform) {
            'airbnb' => app(\App\Infrastructure\ChannelManager\Adapters\AirbnbChannelAdapter::class),
            'booking' => app(\App\Infrastructure\ChannelManager\Adapters\BookingChannelAdapter::class),
            default => null,
        };
    }

    private function syncToChannel(ChannelAdapter $adapter, SynchronizeAvailabilityCommand $command): SyncResult
    {
        try {
            $dates = [];
            foreach ($command->getDates() as $date) {
                $dates[] = [
                    'date' => $date,
                    'available' => $command->available,
                    'property_id' => $command->propertyId,
                ];
            }

            $response = $adapter->pushAvailability($dates);

            if (!$response->success) {
                return SyncResult::failure($response->errorMessage ?? 'Unknown error');
            }

            if ($response->isConflict()) {
                $conflictDetails = $response->getConflictDetails();
                return SyncResult::success(0, [$conflictDetails]);
            }

            return SyncResult::success(count($dates));
        } catch (\Throwable $e) {
            return SyncResult::failure($e->getMessage());
        }
    }

    private function buildCommandFromSyncRecord(ChannelSyncExecution $record): SynchronizeAvailabilityCommand
    {
        return new SynchronizeAvailabilityCommand(
            tenantId: $record->tenant_id,
            propertyId: $record->property_id,
            reservationId: $record->reservation_id,
            operation: $record->operation,
            dateRange: [
                'start' => $record->date_range_start,
                'end' => $record->date_range_end,
            ],
            available: $record->target_availability,
            blockReason: $record->block_reason,
            idempotencyKey: $record->idempotency_key,
            correlationId: $record->correlation_id,
        );
    }
}
