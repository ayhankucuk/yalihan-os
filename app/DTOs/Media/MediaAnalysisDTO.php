<?php

namespace App\DTOs\Media;

/**
 * Media Analysis Result DTO — Sprint 6.3
 *
 * Orchestrator çıktısını taşır.
 */
final class MediaAnalysisDTO
{
    public function __construct(
        public readonly int $ilan_id,
        public readonly int $toplam_fotograf,
        public readonly int $media_health_score,
        public readonly int $media_quality_score,
        public readonly float $tamamlanma_oran,
        public readonly array $oda_detaylari,
        public readonly array $eksik_odalar,
        public readonly ?int $hero_fotograf_id,
        public readonly array $tum_fotograflar,
    ) {}

    public function toArray(): array
    {
        return [
            'ilan_id' => $this->ilan_id,
            'toplam_fotograf' => $this->toplam_fotograf,
            'media_health_score' => $this->media_health_score,
            'health' => $this->getHealthLabel(),
            'media_quality_score' => $this->media_quality_score,
            'tamamlanma_oran' => round($this->tamamlanma_oran, 2),
            'oda_detaylari' => array_map(fn($r) => $r->toArray(), $this->oda_detaylari),
            'eksik_odalar' => $this->eksik_odalar,
            'hero_fotograf_id' => $this->hero_fotograf_id,
            'tum_fotograflar' => array_map(fn($p) => $p->toArray(), $this->tum_fotograflar),
        ];
    }

    public function getHealthLabel(): string
    {
        return match (true) {
            $this->media_health_score >= 80 => 'EXCELLENT',
            $this->media_health_score >= 60 => 'GOOD',
            $this->media_health_score >= 40 => 'FAIR',
            $this->media_health_score >= 20 => 'POOR',
            default => 'MISSING',
        };
    }
}
