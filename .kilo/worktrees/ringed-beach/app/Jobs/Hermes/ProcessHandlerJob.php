<?php

namespace App\Jobs\Hermes;

use App\Models\HandlerExecution;
use App\Services\Hermes\Contracts\HandlerInterface;
use App\Services\Hermes\HandlerExecutionService;
use App\Services\Hermes\DefaultRetryPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 3.6: Hermes Async Queue Foundation
 *
 * Queued job for async handler execution.
 * Implements retry with exponential backoff.
 */
class ProcessHandlerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // We handle retries ourselves via HandlerExecution
    public int $maxExceptions = 1;

    private int $executionId;
    private string $handlerClass;
    private string $eventName;
    private array $payload;
    private ?string $eventId;
    private ?int $tenantId;

    public function __construct(
        int $executionId,
        string $handlerClass,
        string $eventName,
        array $payload,
        ?string $eventId,
        ?int $tenantId
    ) {
        $this->executionId = $executionId;
        $this->handlerClass = $handlerClass;
        $this->eventName = $eventName;
        $this->payload = $payload;
        $this->eventId = $eventId;
        $this->tenantId = $tenantId;

        $this->onQueue(config('hermes.queue_name', 'hermes'));
    }

    public function handle(HandlerExecutionService $executionService): void
    {
        $execution = HandlerExecution::find($this->executionId);

        if (!$execution) {
            Log::error('Hermes: Execution record not found', ['id' => $this->executionId]);
            return;
        }

        // Skip if already processed or dead-lettered
        if (in_array($execution->status, [
            HandlerExecution::STATUS_SUCCESS,
            HandlerExecution::STATUS_DEAD_LETTER
        ])) {
            Log::info('Hermes: Skipping already processed execution', [
                'id' => $this->executionId,
                'status' => $execution->status,
            ]);
            return;
        }

        $executionService->markRunning($execution);

        try {
            $handler = app($this->handlerClass);
            $handler->handle($this->eventName, $this->payload);

            $executionService->markSuccess($execution);

        } catch (\Throwable $e) {
            $shouldRetry = $executionService->handleFailure($execution, $e->getMessage());

            if ($shouldRetry) {
                // Schedule retry with backoff
                $delaySeconds = $executionService->getRetryDelay($execution);

                Log::info('Hermes: Scheduling retry', [
                    'execution_id' => $this->executionId,
                    'attempt' => $execution->attempt_count,
                    'delay_seconds' => $delaySeconds,
                ]);

                // Release back to queue with delay
                $this->release($delaySeconds);
            }
            // If no retry, it becomes dead-letter (already handled by executionService)
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Hermes: ProcessHandlerJob failed permanently', [
            'execution_id' => $this->executionId,
            'handler' => $this->handlerClass,
            'error' => $exception->getMessage(),
        ]);

        $execution = HandlerExecution::find($this->executionId);

        if ($execution) {
            // Ensure it's marked as dead letter
            $executionService = app(HandlerExecutionService::class);
            $executionService->createDeadLetter($execution, $exception->getMessage());
        }
    }

    /**
     * Get execution ID for testing
     */
    public function getExecutionId(): int
    {
        return $this->executionId;
    }

    /**
     * Get handler class for testing
     */
    public function getHandlerClass(): string
    {
        return $this->handlerClass;
    }
}
