<?php

namespace App\Services\Governance\Telemetry;

use App\Contracts\Governance\TelemetryPublisherInterface;
use App\Enums\Governance\GovernanceTelemetryEvent;
use Illuminate\Support\Facades\Log;

final class LogTelemetryPublisher implements TelemetryPublisherInterface
{
    public function publish(
        GovernanceTelemetryEvent $event,
        string $correlationId,
        string $entityType,
        int|string $entityId,
        float $durationMs,
        array $metadata = []
    ): void {
        // Logs directly to standard logger. 
        // In reality, this can map to Datadog/NewRelic via standard laravel channels.
        Log::channel('daily')->info($event->value, [
            'correlation_id' => $correlationId,
            'entity_type'    => $entityType,
            'entity_id'      => $entityId,
            'duration_ms'    => $durationMs,
            'metadata'       => $metadata,
            'timestamp'      => now()->toIso8601String(),
        ]);
    }
}
