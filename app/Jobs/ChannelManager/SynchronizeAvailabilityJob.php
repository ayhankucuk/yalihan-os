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
     *
     * SAAB Decision 4.6 D4.6-B: Attempt ceiling.
     * Covers TRANSPORT_ERROR and RATE_LIMIT within ~90s TTL.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * SAAB Decision 4.6 D4.6-B: Fixed backoff.
     * Exponential backoff deliberately not used — provider rate limits
     * are uniform; fixed 30s backoff is sufficient and simpler.
     */
    public int $backoff = 30;

    /**
     * The maximum number of seconds the job can run before timing out.
     *
     * SAAB Decision 4.6 D4.6-B: Hard ceiling per attempt.
     * External platform calls must complete within 30s.
     */
    public int $timeout = 30;

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
            Log::warning('SynchronizeAvailabilityJob: Attempt failed, will retry', [
                'sync_record_id' => $this->syncRecordId,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
                'error' => $e->getMessage(),
            ]);

            // Record current attempt count on the evidence record.
            // Status remains 'processing' — retry_exhausted is set only by failed().
            $this->recordAttempt($e->getMessage(), $this->attempts());
            throw $e;
        }
    }

    /**
     * Record the current attempt count on the evidence record.
     * Called on every failed attempt (before retry_exhausted is set).
     * Status stays 'processing' — retry_exhausted is terminal per D4.6-D.
     */
    private function recordAttempt(string $errorMessage, int $attempts): void
    {
        try {
            $execution = ChannelSyncExecution::find($this->syncRecordId);
            if ($execution !== null) {
                $execution->update(['attempts' => $attempts]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to record attempt on execution', [
                'sync_record_id' => $this->syncRecordId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     *
     * Laravel calls failed() ONLY after all $tries are exhausted.
     * This is the retry_exhausted terminal state per SAAB Decision 4.6 D4.6-E.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('SynchronizeAvailabilityJob: Permanently failed', [
            'sync_record_id' => $this->syncRecordId,
            'attempts' => $this->attempts(),
            'error' => $exception?->getMessage(),
        ]);

        $this->markExecutionExhausted(
            $exception?->getMessage() ?? 'Unknown error',
            $this->attempts()
        );
    }

    private function markExecutionExhausted(string $errorMessage, int $attempts): void
    {
        try {
            $execution = ChannelSyncExecution::find($this->syncRecordId);
            if ($execution !== null) {
                // D4.6-E: retry_exhausted is the terminal evidence state
                $execution->markRetryExhausted($errorMessage, $attempts);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to mark execution as retry_exhausted', [
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
