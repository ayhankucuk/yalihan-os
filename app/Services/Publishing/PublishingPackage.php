<?php

namespace App\Services\Publishing;

use App\DTOs\Publishing\ChannelPayloadDTO;
use App\DTOs\Publishing\ChannelReadinessDTO;
use App\DTOs\Publishing\PublishingDecisionDTO;
use App\DTOs\Vision\PublishingMediaDTO;

/**
 * Publishing Package DTO — Sprint 6.5
 *
 * Orchestrator çıktısı — tüm kanal payload'larını bir arada taşır.
 * Bu paket hiçbir kanala PUBLISH ETMEZ — sadece payload üretir.
 *
 * Immutable: üretildikten sonra değiştirilemez.
 */
final class PublishingPackage
{
    /**
     * @param  array<string, ChannelPayloadDTO>  $payloads  Kanal bazlı payload'lar
     */
    public function __construct(
        public readonly int $ilanId,
        public readonly int $tenantId,
        public readonly ?int $workspaceId,
        public readonly array $payloads,
        public readonly ChannelReadinessDTO $readiness,
        public readonly ?PublishingDecisionDTO $decision,
        public readonly ?PublishingMediaDTO $visionMedia,
        public readonly string $traceId,
        public readonly string $generatedAt,
        public readonly float $elapsedMs,
    ) {}

    public function toArray(): array
    {
        $payloads = [];
        foreach ($this->payloads as $channel => $payload) {
            $payloads[$channel] = $payload->toArray();
        }

        return [
            'ilan_id' => $this->ilanId,
            'tenant_id' => $this->tenantId,
            'workspace_id' => $this->workspaceId,
            'payloads' => $payloads,
            'readiness' => $this->readiness->toArray(),
            'decision' => $this->decision?->toArray(),
            'vision_media' => $this->visionMedia?->toArray(),
            'trace_id' => $this->traceId,
            'generated_at' => $this->generatedAt,
            'elapsed_ms' => $this->elapsedMs,
        ];
    }

    /**
     * Belirli bir kanalın payload'unu döner.
     */
    public function forChannel(string $channel): ?ChannelPayloadDTO
    {
        return $this->payloads[$channel] ?? null;
    }

    /**
     * Hazır kanalları döner (hata yok + validation geçmiş).
     *
     * @return string[]
     */
    public function readyChannels(): array
    {
        $ready = [];
        foreach ($this->payloads as $channel => $payload) {
            if (!$payload->hasErrors() && $this->readiness->isReady($channel)) {
                $ready[] = $channel;
            }
        }
        return $ready;
    }

    /**
     * Herhangi bir kanal hazır mı?
     */
    public function hasReadyChannel(): bool
    {
        return !empty($this->readyChannels());
    }

    /**
     * Tüm payload'lar hatasız mı?
     */
    public function isErrorFree(): bool
    {
        foreach ($this->payloads as $payload) {
            if ($payload->hasErrors()) {
                return false;
            }
        }
        return true;
    }
}
