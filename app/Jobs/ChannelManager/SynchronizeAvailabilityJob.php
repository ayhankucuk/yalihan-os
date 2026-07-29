<?php

namespace App\Jobs\ChannelManager;

use App\Application\ChannelManager\Services\AvailabilitySynchronizationService;
use App\Domain\ChannelManager\Enums\ChannelManagerEventVocabulary;
use App\Models\ChannelSyncExecution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SynchronizeAvailabilityJob — Queue job for async availability sync
 *
 * Sprint 13 E02: Availability Synchronization
 *
 * Design principles:
 * - Queue-first: DB transaction does NOT call external APIs
 * - Idempotent: same job = same result (via idempotency key)
 * - Replay-safe: replay creates new execution, doesn't mutate original
 * - Tenant-isolated: all operations scoped to tenant
 *
 * Execution flow:
 *   DB transaction committed
 *       ↓
 *   afterCommit → dispatch
 *       ↓
 *   SynchronizeAvailabilityJob
 *       ↓
 *   Canonical availability already updated
 *       ↓
 *   Push to registered channels
 *       ↓
 *   Record result
 */
class SynchronizeAvailabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $syncRecordId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AvailabilitySynchronizationService $service): void
    {
        Log::info('SynchronizeAvailabilityJob: Starting', [
            'sync_record_id' => $this->syncRecordId,
        ]);

        try {
            $result = $service->processQueuedSync($this->syncRecordId);

            Log::info('SynchronizeAvailabilityJob: Completed', [
                'sync_record_id' => $this->syncRecordId,
                'success' => $result->success,
                'synced_count' => $result->syncedCount,
                'conflict_count' => $result->conflictCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('SynchronizeAvailabilityJob: Failed', [
                'sync_record_id' => $this->syncRecordId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->markExecutionFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('SynchronizeAvailabilityJob: Permanently failed', [
            'sync_record_id' => $this->syncRecordId,
            'error' => $exception?->getMessage(),
        ]);

        $this->markExecutionFailed($exception?->getMessage() ?? 'Unknown error');
    }

    private function markExecutionFailed(string $errorMessage): void
    {
        try {
            $execution = ChannelSyncExecution::find($this->syncRecordId);
            if ($execution !== null) {
                $execution->markFailed($errorMessage);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to mark execution as failed', [
                'sync_record_id' => $this->syncRecordId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get unique job ID (for idempotency)
     */
    public function uniqueId(): string
    {
        return 'availability_sync_' . $this->syncRecordId;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'channel-manager',
            'availability-sync',
            'sync_record:' . $this->syncRecordId,
        ];
    }
}
