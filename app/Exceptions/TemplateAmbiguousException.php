<?php

namespace App\Exceptions;

use Exception;

/**
 * Template Ambiguous Exception
 *
 * Thrown when multiple templates match the same kategori_id + yayin_tipi
 * This indicates a data integrity violation (should be prevented by UNIQUE constraint)
 *
 * @see docs/technical/policies/TEMPLATE_RESOLVER_ERROR_CONTRACT.md
 */
class TemplateAmbiguousException extends Exception
{
    protected int $kategoriId;
    protected string $yayinTipi;
    protected int $count;

    public function __construct(int $kategoriId, string $yayinTipi, int $count)
    {
        $this->kategoriId = $kategoriId;
        $this->yayinTipi = $yayinTipi;
        $this->count = $count;

        parent::__construct(
            "Data integrity error: Multiple templates ({$count}) found for kategori_id={$kategoriId}, yayin_tipi=\"{$yayinTipi}\""
        );
    }

    public function getKategoriId(): int
    {
        return $this->kategoriId;
    }

    public function getYayinTipi(): string
    {
        return $this->yayinTipi;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Get context for error response
     */
    public function getContext(): array
    {
        return [
            'kategori_id' => $this->kategoriId,
            'yayin_tipi' => $this->yayinTipi,
            'count' => $this->count,
        ];
    }
}
