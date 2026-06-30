<?php

namespace App\Services\PropertyHub;

use App\Models\AltKategoriYayinTipi;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;

/**
 * Template Context DTO
 *
 * Immutable value object returned by TemplateContextResolver.
 * Contains everything the AI generator needs — no further DB lookups required.
 */
class TemplateContext
{
    public function __construct(
        public readonly ?YayinTipiSablonu $template,
        public readonly AltKategoriYayinTipi $junction,
        public readonly IlanKategori $kategori,
        public readonly ?IlanKategori $parentKategori,
        public readonly string $normalizedKategori,
        public readonly string $normalizedYayinTipi,
        public readonly string $normalizedAltTur,
    ) {}

    /**
     * UPS kombinasyon string (for logging / debug)
     */
    public function kombinasyon(): string
    {
        return "{$this->normalizedKategori}/{$this->normalizedYayinTipi}/{$this->normalizedAltTur}";
    }
}
