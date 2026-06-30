<?php

namespace App\Jobs\Governance;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Contracts\Governance\TelemetryPublisherInterface;
use App\Enums\Governance\GovernanceTelemetryEvent;

class GovernancePublishShadowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $correlationId,
        public readonly string $entityType,
        public readonly int|string $entityId,
        public readonly array $draftPayload
    ) {
    }

    public function handle(TelemetryPublisherInterface $telemetry): void
    {
        $startTime = microtime(true);

        // Dummy shadow calculation (e.g. measuring payload byte size)
        $payloadSize = strlen(json_encode($this->draftPayload));
        
        // Simüle edilen yan etki (side-effect free) legacy V3 testleri burada koşabilir.

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        $telemetry->publish(
            GovernanceTelemetryEvent::SHADOW_EVALUATED,
            $this->correlationId,
            $this->entityType,
            $this->entityId,
            $durationMs,
            [
                'payload_size_bytes' => $payloadSize, 
                'shadow_safe' => true,
                'queue' => $this->queue ?? 'default'
            ]
        );
    }
}
