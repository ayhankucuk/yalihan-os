<?php

namespace App\Jobs\ChannelManager;

use App\Application\ChannelManager\Services\RateSynchronizationService;
use App\Models\ChannelSyncExecution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SynchronizeRatesJob — Queue job for async rate push to Booking.com.
 *
 * Sprint 4.14 — Booking.com Provider Wave 5
 * ADR-W5-03: Rates sync is a separate job from availability sync.
 *
 * Design principles (mirrors SynchronizeAvailabilityJob):
 *  - Queue-first: DB transaction does NOT call external APIs
 *  - Idempotent: same job = same result (via processed_at guard)
 *  - Replay-safe: replay creates new execution, doesn't mutate original
 *  - Tenant-isolated: operations scoped to tenant via service
 *
 * Execution flow:
 *   ChannelSyncExecution record created
 *       ↓
 *   afterCommit → dispatch
 *       ↓
 *   SynchronizeRatesJob
 *       ↓
 *   RateProjectionService → BookingChannelAdapter → Booking.com
 *       ↓
 *   Result recorded in ChannelSyncExecution
 */
class SynchronizeRatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public bool $deleteWhenMissingModels = true;

    /**
     * @param int $syncRecordId  ChannelSyncExecution.id for this rate sync
     */
    public function __construct(
        public readonly int $syncRecordId,
    ) {}

    public function handle(RateSynchronizationService $service): void
    {
        Log::info('SynchronizeRatesJob: Starting', [
            'sync_record_id' => $this->syncRecordId,
        ]);

        try {
            $result = $service->processQueuedSync($this->syncRecordId);

            Log::info('SynchronizeRatesJob: Completed', [
                'sync_record_id'  => $this->syncRecordId,
                'success'        => $result->success,
                'synced_count'   => $result->syncedCount,
                'conflict_count'  => $result->conflictCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('SynchronizeRatesJob: Failed', [
                'sync_record_id' => $this->syncRecordId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            $this->markExecutionFailed($e->getMessage());
            throw $e;
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('SynchronizeRatesJob: Permanently failed', [
            'sync_record_id' => $this->syncRecordId,
            'error'          => $exception?->getMessage(),
        ]);

        $this->markExecutionFailed($exception?->getMessage() ?? 'Unknown error');
    }

    public function uniqueId(): string
    {
        return 'rate_sync_' . $this->syncRecordId;
    }

    public function tags(): array
    {
        return [
            'channel-manager',
            'rate-sync',
            'sync_record:' . $this->syncRecordId,
        ];
    }

    private function markExecutionFailed(string $errorMessage): void
    {
        try {
            $execution = ChannelSyncExecution::find($this->syncRecordId);
            if ($execution !== null) {
                $execution->markFailed($errorMessage);
            }
        } catch (\Throwable $e) {
            Log::error('SynchronizeRatesJob: Failed to mark execution as failed', [
                'sync_record_id' => $this->syncRecordId,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}
