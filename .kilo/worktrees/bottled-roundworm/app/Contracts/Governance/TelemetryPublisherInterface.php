<?php

namespace App\Contracts\Governance;

use App\Enums\Governance\GovernanceTelemetryEvent;

interface TelemetryPublisherInterface
{
    /**
     * Publishes a telemetry event with strictly side-effect free operations.
     */
    public function publish(
        GovernanceTelemetryEvent $event,
        string $correlationId,
        string $entityType,
        int|string $entityId,
        float $durationMs,
        array $metadata = []
    ): void;
}
