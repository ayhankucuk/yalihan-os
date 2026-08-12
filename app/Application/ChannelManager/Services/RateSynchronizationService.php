<?php

namespace App\Application\ChannelManager\Services;

use App\Application\ChannelManager\DTOs\SynchronizeRatesCommand;
use App\Domain\ChannelManager\Models\SyncResult;
use App\Infrastructure\ChannelManager\Adapters\BookingChannelAdapter;
use App\Jobs\ChannelManager\SynchronizeRatesJob;
use App\Models\ChannelSyncExecution;
use App\Models\Ilan;
use App\Models\IlanTakvimSync;
use App\Services\ChannelManager\RateProjectionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * RateSynchronizationService — Canonical rate sync orchestration engine.
 *
 * Sprint 4.14 — Booking.com Provider Wave 5
 * ADR-W5-01: PropertyPricingService is the canonical rate source.
 * ADR-W5-02: Currency is sourced from Ilan.para_birimi (native currency).
 * ADR-W5-03: Separate job from availability sync (independent triggers + endpoints).
 *
 * Responsibilities:
 *  1. Rate projection (delegates to RateProjectionService)
 *  2. Idempotent execution — same command = same result
 *  3. Queue-first — DB record created first, job dispatched after commit
 *  4. Replay-safe — `processed_at` guard prevents double-execution
 *  5. Tenant isolation — ilan must belong to calling tenant
 *
 * Operation flow:
 *   Command received
 *       ↓
 *   Idempotency check
 *       ↓
 *   Record ChannelSyncExecution (immutable)
 *       ↓
 *   afterCommit → SynchronizeRatesJob dispatched
 *       ↓
 *   Job calls processQueuedSync()
 *       ↓
 *   RateProjectionService → adapter → Booking.com
 */
class RateSynchronizationService
{
    public function __construct(
        private readonly RateProjectionService $rateProjectionService,
        private readonly ?BookingChannelAdapter $bookingAdapter = null,
    ) {}

    private function bookingAdapter(): BookingChannelAdapter
    {
        // Allow constructor injection (test isolation) or container resolution (production).
        return $this->bookingAdapter ?? app(BookingChannelAdapter::class);
    }

    /**
     * Synchronize rates for a property.
     *
     * Entry point for external callers (observers, controllers).
     * Dispatches job after DB transaction commits.
     *
     * @return SyncResult
     */
    public function synchronize(SynchronizeRatesCommand $command, int $userId): SyncResult
    {
        $command->validate();
        $this->enforceTenantIsolation($command->tenantId, $command->propertyId);

        $idempotencyKey = $command->getIdempotencyKey();
        $correlationId  = $command->getCorrelationId();

        // Step 1: Idempotency — return existing result if already synced
        $existing = $this->findExistingSync($idempotencyKey, $command->tenantId);
        if ($existing !== null) {
            Log::info('RateSynchronizationService: idempotent hit', [
                'idempotency_key' => $idempotencyKey,
                'existing_sync_id' => $existing->id,
            ]);
            return $this->buildResultFromExistingSync($existing);
        }

        // Step 2: Project rates (no DB write)
        $ratesData = $this->rateProjectionService->projectRates(
            $command->propertyId,
            $command->tenantId,
            $command->fromDate,
            $command->toDate,
        );

        if (empty($ratesData)) {
            return SyncResult::success(0, [], [
                'ilan_not_found_or_no_rates' => true,
                'idempotency_key' => $idempotencyKey,
            ]);
        }

        // Step 3: Record sync execution (immutable event log)
        $syncRecord = ChannelSyncExecution::create([
            'tenant_id'          => $command->tenantId,
            'property_id'        => $command->propertyId,
            'reservation_id'     => null,
            'operation'          => 'rate_sync',
            'block_reason'       => null,
            'date_range_start'   => $command->fromDate,
            'date_range_end'     => $command->toDate,
            'target_availability'=> true,
            'synced_dates'       => array_column($ratesData, 'date'),
            'conflicts'          => [],
            'idempotency_key'    => $idempotencyKey,
            'correlation_id'     => $correlationId,
            'status'             => 'dispatched',
        ]);

        // Step 4: Dispatch queue job after commit
        SynchronizeRatesJob::dispatch($syncRecord->id)
            ->afterCommit();

        return SyncResult::success(
            syncedCount: count($ratesData),
            conflicts: [],
            metadata: [
                'sync_record_id'   => $syncRecord->id,
                'correlation_id'   => $correlationId,
                'idempotency_key'  => $idempotencyKey,
                'nights'           => count($ratesData),
            ],
        );
    }

    /**
     * Process a queued sync (called by SynchronizeRatesJob).
     *
     * @return SyncResult
     */
    public function processQueuedSync(int $syncRecordId): SyncResult
    {
        $syncRecord = ChannelSyncExecution::findOrFail($syncRecordId);

        // Replay safety: already processed → return existing result
        if ($syncRecord->processed_at !== null) {
            return $this->buildResultFromExistingSync($syncRecord);
        }

        // Project rates for this execution
        $ratesData = $this->rateProjectionService->projectRates(
            $syncRecord->property_id,
            $syncRecord->tenant_id,
            $syncRecord->date_range_start,
            $syncRecord->date_range_end,
        );

        if (empty($ratesData)) {
            $syncRecord->markProcessed(0);
            return SyncResult::success(0);
        }

        // Push to registered Booking.com adapter
        $response = $this->bookingAdapter()->pushRates(
            tenantId:     $syncRecord->tenant_id,
            propertyId:   $syncRecord->property_id,
            correlationId: $syncRecord->correlation_id,
            ratesData:    $ratesData,
        );

        if ($response->success) {
            $syncRecord->markProcessed(count($ratesData));
            return SyncResult::success(
                syncedCount: count($ratesData),
                metadata: [
                    'sync_record_id' => $syncRecord->id,
                    'channel_ref'    => $response->channelRef ?? null,
                ],
            );
        }

        $syncRecord->markFailed($response->errorMessage ?? 'Unknown error');
        return SyncResult::failure(
            $response->errorMessage ?? 'Rate sync failed',
            syncedCount: 0,
        );
    }

    // ─── Private helpers ────────────────────────────────────────────────

    private function enforceTenantIsolation(int $tenantId, int $propertyId): void
    {
        $property = Ilan::where('id', $propertyId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($property === null) {
            throw new \RuntimeException(
                "Property {$propertyId} not found for tenant {$tenantId}"
            );
        }
    }

    private function findExistingSync(string $idempotencyKey, int $tenantId): ?ChannelSyncExecution
    {
        return ChannelSyncExecution::where('idempotency_key', $idempotencyKey)
            ->where('tenant_id', $tenantId)
            ->where('operation', 'rate_sync')
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
                'sync_record_id'   => $sync->id,
                'idempotent'       => true,
                'original_created_at' => $sync->created_at->toIso8601String(),
            ],
        );
    }
}
