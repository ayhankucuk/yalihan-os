<?php

namespace App\Services\Governance\Telemetry;

use App\Contracts\Governance\TelemetryPublisherInterface;
use App\Enums\Governance\GovernanceTelemetryEvent;
use Illuminate\Support\Facades\Log;

/**
 * ChainedTelemetryPublisher — Phase 4C
 *
 * Birden fazla TelemetryPublisher'ı sırayla çağırır.
 * Şu anda: Redis (gerçek zamanlı) + Log (audit trail).
 *
 * SAB: Tek bir interface'e bağlı kalınır, concrete'ler chain içinde
 */
final class ChainedTelemetryPublisher implements TelemetryPublisherInterface
{
    /** @param TelemetryPublisherInterface[] $publishers */
    public function __construct(
        private readonly array $publishers
    ) {}

    public function publish(
        GovernanceTelemetryEvent $event,
        string $correlationId,
        string $entityType,
        int|string $entityId,
        float $durationMs,
        array $metadata = []
    ): void {
        foreach ($this->publishers as $publisher) {
            try {
                $publisher->publish($event, $correlationId, $entityType, $entityId, $durationMs, $metadata);
            } catch (\Throwable $e) {
                // @sab-ignore-catch — Zincir bir halkanın çökmesi diğerlerini durdurmaz
                Log::channel('daily')->error('ChainedTelemetryPublisher: publisher failed', [
                    'publisher' => get_class($publisher),
                    'error'     => $e->getMessage(),
                ]);
            }
        }
    }
}
