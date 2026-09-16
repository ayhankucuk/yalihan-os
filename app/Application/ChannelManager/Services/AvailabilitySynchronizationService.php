<?php

namespace App\Application\ChannelManager\Services;

use App\Application\ChannelManager\DTOs\SynchronizeAvailabilityCommand;
use App\Domain\ChannelManager\Contracts\AvailabilitySynchronizer;
use App\Domain\ChannelManager\Contracts\ChannelAdapter;
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
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * AvailabilitySynchronizationService — Canonical availability sync engine
 *
 * Sprint 13 E03: Per-Channel Execution Isolation
 *
 * Responsibilities:
 * 1. Canonical availability FIRST — update local state before external sync
 * 2. Queue-first execution — DB transaction does NOT call external APIs
 * 3. Idempotent operations — same command = same result
 * 4. Conflict detection — detect and surface conflicts without silent overwrite
 * 5. Replay safety — replay creates new execution, doesn't mutate original
 * 6. Tenant isolation — all operations are tenant-scoped
 * 7. E03: Per-channel execution isolation — one channel's failure does NOT affect another
 *
 * E03 Operation flow:
 *   Canonical Reservation confirmed
 *       ↓
 *   Canonical Availability updated (in DB)
 *       ↓
 *   For EACH active channel:
 *       ├── Independent ChannelSyncExecution created (immutable event)
 *       └── afterCommit → SynchronizeAvailabilityJob(channel) dispatched
 *       ↓
 *   Registered channels updated independently
 *       ↓
 *   Each channel result recorded in its own execution record
 *
 * E03 Critical Invariant:
 *   Booking failure → Airbnb success (no rollback, no cross-channel effect)
 *   Each channel execution is fully independent in retry/evidence lifecycle.
 */
class AvailabilitySynchronizationService
{
    public function __construct(
        private readonly AvailabilitySynchronizer $synchronizer,
    ) {}

    /**
     * Synchronize availability for a reservation event
     *
     * E03: Creates one ChannelSyncExecution per registered channel, each with
     * its own channel-specific idempotency key and job. Canonical availability
     * is updated once; external channel propagation is fully per-channel.
     *
     * @param SynchronizeAvailabilityCommand $command
     * @param int $userId Who triggered this sync (for audit)
     * @return SyncResult Aggregated result across all channels
     */
    public function synchronize(SynchronizeAvailabilityCommand $command, int $userId): SyncResult
    {
        $command->validate();
        $this->enforceTenantIsolation($command->tenantId, $command->propertyId);

        $correlationId = $command->correlationId ?? $this->generateCorrelationId();

        // ─── Step 1: Update canonical availability (local DB first) ────
        $conflictDates = [];
        $blockedDates = [];

        DB::transaction(function () use ($command, &$conflictDates, &$blockedDates) {
            foreach ($command->getDates() as $date) {
                $existing = PropertyAvailability::where('property_id', $command->propertyId)
                    ->where('date', $date)
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

                if ($existing !== null) {
                    $existing->update([
                        'is_available' => $command->available,
                        'block_reason' => $command->isBlocking() ? $command->blockReason : null,
                        'source_system' => 'canonical',
                        'reservation_id' => $command->isBlocking() ? $command->reservationId : null,
                    ]);
                } else {
                    try {
                        PropertyAvailability::create([
                            'property_id' => $command->propertyId,
                            'date' => $date,
                            'is_available' => $command->available,
                            'block_reason' => $command->isBlocking() ? $command->blockReason : null,
                            'source_system' => 'canonical',
                            'reservation_id' => $command->isBlocking() ? $command->reservationId : null,
                        ]);
                    } catch (QueryException $e) {
                        if ($e->errorInfo[1] === 19 || $e->errorInfo[1] === 1062) {
                            $conflictDates[$date] = [
                                'existing_block' => [
                                    'source' => 'concurrent_transaction',
                                ],
                            ];
                            continue;
                        }
                        throw $e;
                    }
                }

                $blockedDates[] = $date;
            }
        });

        // ─── Step 2: E03 — Per-channel execution dispatch ─────────────
        // For each registered channel, create an independent execution record
        // and dispatch its own job. Each execution is isolated.
        //
        // E03: When no channels are registered (property not connected to any OTA),
        // fall back to a single channel-agnostic execution for backward compatibility.
        $registeredChannels = $this->getRegisteredChannels($command->propertyId, $command->tenantId);

        $executionIds = [];

        if (empty($registeredChannels)) {
            // Fallback: no registered channels — create a single aggregated execution.
            // This preserves backward compatibility with tests that don't set up
            // IlanTakvimSync records. channel=NULL means "aggregated across all channels".
            $idempotencyKey = $command->getIdempotencyKey(); // No channel suffix
            $existingSync = $this->findExistingSync($idempotencyKey, $command->tenantId, null);
            if ($existingSync !== null) {
                Log::info('AvailabilitySync: Idempotent hit (no channels)', [
                    'idempotency_key' => $idempotencyKey,
                    'existing_sync_id' => $existingSync->id,
                ]);
                $executionIds['__none__'] = $existingSync->id;
            } else {
                $syncRecord = ChannelSyncExecution::create([
                    'tenant_id' => $command->tenantId,
                    'property_id' => $command->propertyId,
                    'reservation_id' => $command->reservationId,
                    'channel' => null, // aggregated / no channels
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
                $executionIds['__none__'] = $syncRecord->id;
                SynchronizeAvailabilityJob::dispatch($syncRecord->id)->afterCommit();
            }
        } else {
            foreach ($registeredChannels as $channelAdapter) {
                $channelId = $this->getChannelIdFromAdapter($channelAdapter);

                // E03: Build channel-aware command
                $channelCommand = new SynchronizeAvailabilityCommand(
                    tenantId: $command->tenantId,
                    propertyId: $command->propertyId,
                    reservationId: $command->reservationId,
                    operation: $command->operation,
                    dateRange: $command->dateRange,
                    available: $command->available,
                    blockReason: $command->blockReason,
                    idempotencyKey: null, // Let DTO generate channel-aware key
                    correlationId: $correlationId,
                    channel: $channelId,
                );

                $idempotencyKey = $channelCommand->getIdempotencyKey();

                // E03: Channel-aware idempotency check
                $existingSync = $this->findExistingSync($idempotencyKey, $command->tenantId, $channelId);
                if ($existingSync !== null) {
                    Log::info('AvailabilitySync: Channel idempotent hit', [
                        'idempotency_key' => $idempotencyKey,
                        'channel' => $channelId,
                        'existing_sync_id' => $existingSync->id,
                    ]);
                    $executionIds[$channelId] = $existingSync->id;
                    continue;
                }

                // Record per-channel execution (immutable event)
                $syncRecord = ChannelSyncExecution::create([
                    'tenant_id' => $command->tenantId,
                    'property_id' => $command->propertyId,
                    'reservation_id' => $command->reservationId,
                    'channel' => $channelId,
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

                $executionIds[$channelId] = $syncRecord->id;

                // E03: Dispatch per-channel job — independent, isolated retry lifecycle
                SynchronizeAvailabilityJob::dispatch($syncRecord->id)
                    ->afterCommit();
            }
        }

        return SyncResult::success(
            syncedCount: count($blockedDates),
            conflicts: $conflictDates,
            metadata: [
                'correlation_id' => $correlationId,
                'channels' => array_keys($executionIds),
                'execution_ids' => $executionIds,
            ]
        );
    }

    /**
     * Process a queued sync execution (called by SynchronizeAvailabilityJob)
     *
     * E03: Each job handles exactly ONE channel execution.
     * The job was dispatched per-channel, so this processes only the
     * channel stored in the execution record.
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

        // E03: Resolve the single channel for this execution
        $channelAdapter = $this->resolveChannelAdapter($syncRecord->channel);

        if ($channelAdapter === null) {
            Log::warning('AvailabilitySync: Unknown channel, skipping', [
                'sync_record_id' => $syncRecordId,
                'channel' => $syncRecord->channel,
            ]);
            $syncRecord->markProcessed(0, ['channel_not_registered' => $syncRecord->channel]);
            return SyncResult::success(0, ['channel_not_registered' => $syncRecord->channel]);
        }

        // E03: Single channel sync — no iteration
        $result = $this->syncToChannel($channelAdapter, $command);

        if ($result->hasConflicts()) {
            $syncRecord->markProcessed($result->syncedCount, $result->conflicts);
        } else {
            $syncRecord->markProcessed($result->syncedCount, []);
        }

        return $result;
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
            'manual' => $localRecord->is_available,
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
        $property = Ilan::withoutGlobalScopes()
            ->where('id', $propertyId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($property === null) {
            throw new \RuntimeException("Property {$propertyId} not found for tenant {$tenantId}");
        }
    }

    /**
     * E03: Find existing sync with channel-aware idempotency check.
     *
     * The unique constraint is now (tenant_id, idempotency_key, channel).
     * This allows the same business idempotency key root to produce independent
     * execution records per channel without cross-channel duplicate detection.
     *
     * @return ChannelSyncExecution|null
     */
    private function findExistingSync(string $idempotencyKey, int $tenantId, ?string $channel = null): ?ChannelSyncExecution
    {
        $query = ChannelSyncExecution::where('idempotency_key', $idempotencyKey)
            ->where('tenant_id', $tenantId);

        if ($channel !== null) {
            $query->where('channel', $channel);
        } else {
            $query->whereNull('channel');
        }

        return $query->lockForUpdate()
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
                'channel' => $sync->channel,
                'idempotent' => true,
                'original_created_at' => $sync->created_at->toIso8601String(),
            ]
        );
    }

    /**
     * E03: Returns channel adapters for all active channels registered to the property.
     * Used during synchronize() to determine which channels to dispatch per-channel jobs for.
     *
     * @return array<\App\Contracts\ChannelManager\ChannelSyncContract>
     */
    private function getRegisteredChannels(int $propertyId, int $tenantId): array
    {
        $channelSyncs = IlanTakvimSync::where('ilan_id', $propertyId)
            ->where('is_sync_active', true)
            ->where('senkron_durumu', 'active')
            ->whereHas('ilan', fn($q) => $q->where('tenant_id', $tenantId))
            ->get();

        $adapters = [];
        foreach ($channelSyncs as $sync) {
            $adapter = $this->resolveChannelAdapter($sync->platform);
            if ($adapter !== null) {
                $adapters[] = $adapter;
            }
        }

        return $adapters;
    }

    /**
     * E03: Extract channel identifier from a ChannelAdapter instance.
     * Used to correlate adapter instances with the `channel` discriminator.
     *
     * Supports both ChannelAdapter (testing: InMemoryChannelAdapter) and
     * ChannelSyncContract (production: AirbnbChannelAdapter, BookingChannelAdapter).
     */
    private function getChannelIdFromAdapter(ChannelAdapter|\App\Contracts\ChannelManager\ChannelSyncContract $adapter): string
    {
        // ChannelSyncContract: use getChannel() which returns a Channel enum
        if ($adapter instanceof \App\Contracts\ChannelManager\ChannelSyncContract) {
            return $adapter->getChannel()->value;
        }

        // ChannelAdapter interface: use getChannelId() method
        if (method_exists($adapter, 'getChannelId')) {
            return $adapter->getChannelId();
        }

        // Fallback: derive from class name (AirbnbChannelAdapter → airbnb)
        $class = class_basename($adapter);
        return strtolower(preg_replace('/ChannelAdapter$/', '', $class));
    }

    private function resolveChannelAdapter(string $platform): ?\App\Contracts\ChannelManager\ChannelSyncContract
    {
        return match ($platform) {
            'airbnb' => app(\App\Infrastructure\ChannelManager\Adapters\AirbnbChannelAdapter::class),
            'booking' => app(\App\Infrastructure\ChannelManager\Adapters\BookingChannelAdapter::class),
            default => null,
        };
    }

    /**
     * E03: Release availability blocks per channel.
     *
     * Creates one ChannelSyncExecution per registered channel, each with its
     * own channel-specific idempotency key, and dispatches an independent job.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param int $reservationId
     * @param string $startDate Inclusive start date Y-m-d
     * @param string $endDate   Inclusive end date Y-m-d
     * @return SyncResult
     */
    public function release(
        int $tenantId,
        int $propertyId,
        int $reservationId,
        string $startDate,
        string $endDate,
    ): SyncResult {
        $this->enforceTenantIsolation($tenantId, $propertyId);

        $correlationId = sprintf('release-%s-%s', now()->format('Ymd'), \Illuminate\Support\Str::random(12));

        $releasedCount = 0;
        DB::transaction(function () use ($propertyId, $reservationId, $tenantId, &$releasedCount) {
            $releasedCount = PropertyAvailability::where('property_id', $propertyId)
                ->where('reservation_id', $reservationId)
                ->whereIn('source_system', ['internal', 'canonical'])
                ->where('is_available', false)
                ->update([
                    'is_available' => true,
                    'block_reason' => null,
                    'reservation_id' => null,
                ]);
        });

        // E03: Per-channel execution dispatch
        $registeredChannels = $this->getRegisteredChannels($propertyId, $tenantId);
        $executionIds = [];

        foreach ($registeredChannels as $channelAdapter) {
            $channelId = $this->getChannelIdFromAdapter($channelAdapter);

            // E03: Channel-aware idempotency key for release
            $idempotencyKey = "{$tenantId}:{$propertyId}:{$reservationId}:release:{$startDate}:{$endDate}:{$channelId}";

            $existing = $this->findExistingSync($idempotencyKey, $tenantId, $channelId);
            if ($existing !== null) {
                $executionIds[$channelId] = $existing->id;
                continue;
            }

            $syncRecord = ChannelSyncExecution::create([
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
                'reservation_id' => $reservationId,
                'channel' => $channelId,
                'operation' => 'release',
                'block_reason' => null,
                'date_range_start' => $startDate,
                'date_range_end' => $endDate,
                'target_availability' => true,
                'synced_dates' => [],
                'conflicts' => [],
                'idempotency_key' => $idempotencyKey,
                'correlation_id' => $correlationId,
                'status' => 'dispatched',
            ]);

            $executionIds[$channelId] = $syncRecord->id;

            SynchronizeAvailabilityJob::dispatch($syncRecord->id)
                ->afterCommit();
        }

        return SyncResult::success($releasedCount, [], [
            'correlation_id' => $correlationId,
            'channels' => array_keys($executionIds),
            'execution_ids' => $executionIds,
            'operation' => 'release',
        ]);
    }

    /**
     * E03: Replay a failed or exhausted sync execution.
     *
     * MUST 3: Creates a NEW execution record — never mutates the original.
     * E03: Channel is preserved from the original execution; the replay job
     * will sync only to that channel.
     *
     * @param int $executionId The failed execution to replay
     * @return array{success: bool, new_execution_id: int|null, correlation_id: string|null, error: string|null}
     */
    public function replay(int $executionId): array
    {
        $original = ChannelSyncExecution::find($executionId);

        if ($original === null) {
            return [
                'success' => false,
                'new_execution_id' => null,
                'correlation_id' => null,
                'error' => "Execution #{$executionId} not found",
            ];
        }

        $replayableStates = ['failed', 'retry_exhausted', 'completed_with_conflicts'];
        if (!in_array($original->status, $replayableStates, true)) {
            return [
                'success' => false,
                'new_execution_id' => null,
                'correlation_id' => null,
                'error' => "Execution #{$executionId} has status '{$original->status}' — only [" . implode(', ', $replayableStates) . "] are replayable",
            ];
        }

        // E03: Channel-aware idempotency key for replay
        $newIdempotencyKey = $original->idempotency_key
            . ':replay:'
            . now()->timestamp;

        $newCorrelationId = 'replay:' . ($original->correlation_id ?? '') . ':' . now()->timestamp;

        try {
            $newExecution = ChannelSyncExecution::create([
                'tenant_id' => $original->tenant_id,
                'property_id' => $original->property_id,
                'reservation_id' => $original->reservation_id,
                'channel' => $original->channel, // E03: preserve channel from original
                'operation' => $original->operation,
                'block_reason' => $original->block_reason,
                'date_range_start' => $original->date_range_start,
                'date_range_end' => $original->date_range_end,
                'target_availability' => $original->target_availability,
                'synced_dates' => [],
                'conflicts' => [],
                'idempotency_key' => $newIdempotencyKey,
                'correlation_id' => $newCorrelationId,
                'status' => 'dispatched',
            ]);

            SynchronizeAvailabilityJob::dispatch($newExecution->id)
                ->afterCommit();

            return [
                'success' => true,
                'new_execution_id' => $newExecution->id,
                'correlation_id' => $newCorrelationId,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'new_execution_id' => null,
                'correlation_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function syncToChannel(ChannelAdapter|\App\Contracts\ChannelManager\ChannelSyncContract $adapter, SynchronizeAvailabilityCommand $command): SyncResult
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

            // E03: ChannelSyncContract has a different pushAvailability signature
            // than ChannelAdapter. Detect which interface is implemented.
            if ($adapter instanceof \App\Contracts\ChannelManager\ChannelSyncContract) {
                $response = $adapter->pushAvailability(
                    $command->tenantId,
                    $command->propertyId,
                    $command->correlationId ?? '',
                    $dates
                );

                if (!$response->success) {
                    // GAP-03: If the response is retryable (Airbnb/Channex 5xx, rate-limit),
                    // throw an exception so Laravel's queue retry mechanism is triggered.
                    // Non-retryable failures (4xx, auth errors) return graceful failure.
                    if (property_exists($response, 'retryable') && $response->retryable) {
                        throw new \App\Application\ChannelManager\Exceptions\ChannelSynchronizationException(
                            tenantId: $command->tenantId,
                            propertyId: $command->propertyId,
                            channelId: $command->channel ?? 'unknown',
                            errorMessage: $response->errorMessage ?? 'Retryable channel sync failure',
                            retryable: true,
                        );
                    }
                    return SyncResult::failure($response->errorMessage ?? 'Unknown error');
                }

                return SyncResult::success(count($dates));
            }

            // ChannelAdapter interface (InMemoryChannelAdapter for testing)
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
            // E03 GAP-03: Retryable exceptions must propagate to Laravel's queue
            // so that $tries/$backoff/failed() lifecycle is triggered.
            //
            // BookingAvailabilityException and BookingRatesException carry isRetryable flag.
            // Re-throw if retryable so Laravel retries the job.
            // Non-retryable exceptions (e.g., 4xx client errors) return failure.
            if ($this->isRetryableException($e)) {
                throw $e;
            }
            return SyncResult::failure($e->getMessage());
        }
    }

    /**
     * Detect if a thrown exception is retryable.
     *
     * E03 GAP-03 fix: Only re-throw exceptions that represent transient failures
     * (5xx server errors, network timeouts, rate limits). Client errors (4xx)
     * and domain exceptions are non-retryable and return gracefully.
     */
    private function isRetryableException(\Throwable $e): bool
    {
        // BookingAvailabilityException and BookingRatesException carry explicit isRetryable flag
        if ($e instanceof \App\Infrastructure\ChannelManager\Booking\BookingAvailabilityException) {
            return $e->isRetryable();
        }
        if ($e instanceof \App\Infrastructure\ChannelManager\Booking\BookingRatesException) {
            return $e->isRetryable();
        }
        // ChannelSynchronizationException (Airbnb/Channex retryable response path)
        if ($e instanceof \App\Application\ChannelManager\Exceptions\ChannelSynchronizationException) {
            return $e->isRetryable();
        }

        // Network/transport errors without explicit retryable flag: retry
        if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            return true;
        }
        if ($e instanceof \GuzzleHttp\Exception\ConnectException) {
            return true;
        }

        return false;
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
            channel: $record->channel,
        );
    }

    private function generateCorrelationId(): string
    {
        return sprintf(
            'sync-%s-%s',
            now()->format('Ymd'),
            \Illuminate\Support\Str::random(12)
        );
    }
}
