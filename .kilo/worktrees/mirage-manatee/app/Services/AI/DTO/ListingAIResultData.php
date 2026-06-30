<?php

namespace App\Services\AI\DTO;

/**
 * 🧱 IMMUTABLE AI CONTRACT
 * Model-agnostic data structure for real estate listings.
 */
final class ListingAIResultData
{
    public function __construct(
        public readonly string $baslik,
        public readonly string $aciklama,
        public readonly string $tip,
        public readonly string $kategori,
        public readonly array $ozellikler,
        public readonly array $one_cikanlar,
        public readonly ?string $warning = null
    ) {}
}
