<?php

namespace App\Events\Publishing;

use App\Services\Publishing\PublishingPackage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Publishing Package Ready Event — Sprint 6.5
 *
 * Publishing Intelligence pipeline tamamlandığında fırlatılır.
 * Immutable: event verisi değiştirilemez.
 *
 * DOWNSTREAM: Channel handler'lar (Sprint 6.6) bu event'i dinler.
 * NOT: Bu event PUBLISH TETIKLAMAZ — sadece hazırlık tamamlandığını işaretler.
 * Real publish Sprint 6.6'da yapılacaktır.
 */
final class PublishingPackageReady
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PublishingPackage $package,
    ) {}

    public function toArray(): array
    {
        return [
            'ilan_id' => $this->package->ilanId,
            'tenant_id' => $this->package->tenantId,
            'workspace_id' => $this->package->workspaceId,
            'ready_channels' => $this->package->readyChannels(),
            'has_ready_channel' => $this->package->hasReadyChannel(),
            'is_error_free' => $this->package->isErrorFree(),
            'decision' => $this->package->decision?->decision,
            'quality_tier' => $this->package->decision?->qualityTier,
            'vision_score' => $this->package->visionMedia?->vision_score,
            'trace_id' => $this->package->traceId,
            'generated_at' => $this->package->generatedAt,
            'elapsed_ms' => $this->package->elapsedMs,
        ];
    }
}
