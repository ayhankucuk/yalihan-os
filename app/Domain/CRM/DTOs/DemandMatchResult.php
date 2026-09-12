<?php

namespace App\Domain\CRM\DTOs;

/**
 * DemandMatchResult — Domain DTO
 *
 * Encapsulates the evaluation result of matching an Ilan (Listing) with a Talep (Demand).
 */
readonly class DemandMatchResult
{
    public function __construct(
        public int $talepId,
        public string $talepBaslik,
        public ?int $kisiId,
        public ?string $kisiAdSoyad,
        public ?int $danismanId,
        public int $ilanId,
        public float $score,
        public string $matchLevel = 'WEAK', // STRONG, GOOD, WEAK, IGNORE
        public string $urgencyLevel = 'NORMAL',
        public array $matchReasons = [],
        public array $metadata = []
    ) {}

    public function isActionable(): bool
    {
        return $this->matchLevel !== 'IGNORE' && $this->score >= 50;
    }

    public function isHighPriority(): bool
    {
        return $this->score >= 80 || $this->matchLevel === 'STRONG' || in_array($this->urgencyLevel, ['HIGH', 'CRITICAL'], true);
    }

    public function toArray(): array
    {
        return [
            'talep_id'      => $this->talepId,
            'talep_baslik'  => $this->talepBaslik,
            'kisi_id'       => $this->kisiId,
            'kisi_ad_soyad' => $this->kisiAdSoyad,
            'danisman_id'   => $this->danismanId,
            'ilan_id'       => $this->ilanId,
            'score'         => $this->score,
            'urgency_level' => $this->urgencyLevel,
            'match_reasons' => $this->matchReasons,
            'metadata'      => $this->metadata,
        ];
    }
}
