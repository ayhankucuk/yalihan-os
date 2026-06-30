<?php

namespace App\Jobs\CQRS;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class ProcessProjectionJob
 *
 * SAB Phase 15 Sprint 1: Asynchronous Read Model Projection Handler
 * Processes domain events and updates read model projections.
 *
 * Anayasal Kararlar:
 * - Madde 1: Asenkron işleme (write path latency korunur)
 * - Madde 2: Eventual consistency (read model gecikebilir)
 * - Madde 3: Idempotent processing (aynı event tekrar işlenebilir)
 * - Madde 4: Fail-loud logging
 *
 * @package App\Jobs\CQRS
 */
class ProcessProjectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Domain event data
     *
     * @var array
     */
    protected array $eventData;

    /**
     * Number of times the job may be attempted
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Number of seconds to wait before retrying
     *
     * @var int
     */
    public $backoff = 5;

    /**
     * Create a new job instance
     *
     * @param array $eventData
     */
    public function __construct(array $eventData)
    {
        $this->eventData = $eventData;
        $this->onQueue('projections'); // Dedicated projection queue
    }

    /**
     * Execute the job
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $eventType = $this->eventData['event_type'] ?? null;
            $aggregateType = $this->eventData['aggregate_type'] ?? null;

            if (!$eventType || !$aggregateType) {
                Log::warning('Projection job received incomplete event data', [
                    'event_data' => $this->eventData,
                ]);
                return;
            }

            // Route event to appropriate projection handler
            $this->routeToProjection($aggregateType, $eventType, $this->eventData);

            Log::info('Projection processed successfully', [
                'event_type' => $eventType,
                'aggregate_type' => $aggregateType,
                'aggregate_id' => $this->eventData['aggregate_id'] ?? 0,
            ]);

        } catch (\Throwable $exception) {
            Log::critical("PROJECTION PROCESSING FAILURE: {$exception->getMessage()}", [
                'event_data' => $this->eventData,
                'exception_class' => get_class($exception),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $exception;
        }
    }

    /**
     * Route event to appropriate projection handler
     *
     * @param string $aggregateType
     * @param string $eventType
     * @param array $eventData
     * @return void
     */
    protected function routeToProjection(string $aggregateType, string $eventType, array $eventData): void
    {
        match ($aggregateType) {
            'App\Domain\CQRS\Aggregates\LeadAggregate' => app(\App\Domain\CQRS\Projections\LeadProjectionHandler::class)->handle($eventData),
            'App\Domain\CQRS\Aggregates\IlanAggregate' => app(\App\Domain\CQRS\Projections\IlanProjectionHandler::class)->handle($eventData),
            'App\Domain\CQRS\Aggregates\KisiAggregate' => app(\App\Domain\CQRS\Projections\KisiProjeksiyonYoneticisi::class)->handle($eventData),
            default => Log::warning('Unknown aggregate type for projection routing', [
                'aggregate_type' => $aggregateType,
                'event_type' => $eventType,
            ]),
        };
    }

    /**
     * Handle a job failure
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Projection job failed after all retries', [
            'event_data' => $this->eventData,
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Dead letter queue (etki_alani_olaylari_hatali) and forensic logging are handled at store layer
    }
}
