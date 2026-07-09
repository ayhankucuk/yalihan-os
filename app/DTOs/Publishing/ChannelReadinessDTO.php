<?php

namespace App\DTOs\Publishing;

use App\DTOs\Publishing\ChannelReadinessItem;

/**
 * Channel Readiness DTO — Sprint 6.5
 *
 * Her kanal için publishing hazırlık durumunu değerlendirir.
 * Adapters'ın requiredFields() + Ilan verilerini karşılaştırır.
 */
final class ChannelReadinessDTO
{
    /**
     * @param  array<string, ChannelReadinessItem>  $channels
     * @param  string[]                             $globalIssues  Tüm kanalları etkileyen eksiklikler
     */
    public function __construct(
        public readonly int $ilanId,
        public readonly array $channels,
        public readonly array $globalIssues = [],
        public readonly bool $isGloballyReady = false,
    ) {}

    public function toArray(): array
    {
        $channels = [];
        foreach ($this->channels as $key => $item) {
            $channels[$key] = $item->toArray();
        }

        return [
            'ilan_id' => $this->ilanId,
            'channels' => $channels,
            'global_issues' => $this->globalIssues,
            'is_globally_ready' => $this->isGloballyReady,
            'ready_channel_count' => count(array_filter(
                $channels,
                fn($c) => $c['is_ready']
            )),
            'total_channel_count' => count($channels),
            'assessed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Belirli bir kanalın hazır olup olmadığını döner.
     */
    public function isReady(string $channel): bool
    {
        return $this->channels[$channel]?->isReady() ?? false;
    }

    /**
     * Hazır kanalları döner.
     *
     * @return ChannelReadinessItem[]
     */
    public function readyChannels(): array
    {
        return array_filter(
            $this->channels,
            fn(ChannelReadinessItem $item) => $item->isReady()
        );
    }
}
