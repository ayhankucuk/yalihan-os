<?php

namespace App\Domain\CQRS\Messaging;

use App\Jobs\CQRS\ProcessProjectionJob;
use Illuminate\Support\Facades\Log;

use App\Models\EtkiAlaniOlayi;
use Illuminate\Support\Facades\Auth;

/**
 * Class EventDispatcher
 *
 * SAB Phase 15 Sprint 1: High-Velocity Domain Event Dispatcher
 * Dispatches domain events downstream to projection engines without affecting write path latency.
 *
 * Anayasal Kararlar:
 * - Madde 1: Write path latency <10ms korunmalı
 * - Madde 2: Eventual consistency kabul edilebilir
 * - Madde 3: Event Store kalıcılığı zorunludur (etki_alani_olaylari)
 * - Madde 4: Fail-loud logging (exception swallowing yasak)
 *
 * @package App\Domain\CQRS\Messaging
 */
class EventDispatcher
{
    /**
     * Dispatch domain events to projection handlers
     *
     * @param array<int, array<string, mixed>> $events
     * @return void
     */
    public function dispatch(array $events): void
    {
        foreach ($events as $event) {
            try {
                // Event Store kalıcılığı (Write-Ahead Log)
                EtkiAlaniOlayi::create([
                    'tenant_id' => $event['tenant_id'] ?? 1,
                    'aggregate_type' => $event['aggregate_type'] ?? 'Unknown',
                    'aggregate_id' => $event['aggregate_id'] ?? 0,
                    'event_type' => $event['event_type'] ?? 'Unknown',
                    'sequence_number' => $event['sequence_number'] ?? 1,
                    'payload' => $event['payload'] ?? [],
                    'user_id' => Auth::id(),
                    'ip_adresi' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                // Latans bütçesini (<10ms) korumak için projeksiyon senkronizasyonunu asenkron kuyruğa it
                dispatch(new ProcessProjectionJob($event));

                Log::info('Domain event dispatched to projection queue and persisted to event store', [
                    'event_type' => $event['event_type'] ?? 'Unknown',
                    'aggregate_type' => $event['aggregate_type'] ?? 'Unknown',
                    'aggregate_id' => $event['aggregate_id'] ?? 0,
                    'tenant_id' => $event['tenant_id'] ?? 0,
                ]);

            } catch (\Throwable $exception) {
                // SAB Madde 2: Fail-Loud Logging
                Log::critical("EVENT DISPATCH FAILURE: {$exception->getMessage()}", [
                    'event_type' => $event['event_type'] ?? 'Unknown',
                    'aggregate_type' => $event['aggregate_type'] ?? 'Unknown',
                    'tenant_id' => $event['tenant_id'] ?? 0,
                    'exception_class' => get_class($exception),
                    'trace' => $exception->getTraceAsString(),
                ]);

                // Re-throw to ensure visibility
                throw $exception;
            }
        }
    }

    /**
     * Dispatch single event (convenience method)
     *
     * @param array $event
     * @return void
     */
    public function dispatchSingle(array $event): void
    {
        $this->dispatch([$event]);
    }
}
