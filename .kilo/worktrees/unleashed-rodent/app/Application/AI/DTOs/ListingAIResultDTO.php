<?php

namespace App\Application\AI\DTOs;

/**
 * 🧱 AI CONTRACT: Listing Generation
 * This DTO is the Single Source of Truth for Listing AI results.
 * Models may change, but this contract remains immutable.
 */
final class ListingAIResultDTO
{
    public function __construct(
        public readonly string $baslik,
        public readonly string $aciklama,
        public readonly string $tip,
        public readonly string $kategori,
        public readonly array $ozellikler,
        public readonly array $one_cikanlar,
        public readonly array $meta = []
    ) {}

    /**
     * Array'e dönüştür (UI/API uyumu için)
     */
    public function toArray(): array
    {
        return [
            'baslik' => $this->baslik,
            'aciklama' => $this->aciklama,
            'tip' => $this->tip,
            'kategori' => $this->kategori,
            'ozellikler' => $this->ozellikler,
            'one_cikanlar' => $this->one_cikanlar,
        ];
    }
}
