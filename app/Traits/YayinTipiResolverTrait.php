<?php

namespace App\Traits;

use App\Models\YayinTipiSablonu;

/**
 * Trait YayinTipiResolverTrait
 *
 * Provides centralized resolution of yayin_tipi_id to canonical string names.
 * Part of WFC-002 refactor.
 */
trait YayinTipiResolverTrait
{
    /**
     * Resolves a yayin_tipi_id to its string name or throws an exception.
     *
     * @param int|string $yayinTipiId
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function resolveYayinTipiNameOrFail($yayinTipiId): string
    {
        if (empty($yayinTipiId)) {
            throw new \InvalidArgumentException("yayin_tipi_id is required");
        }

        // V2: YayinTipiSablonu (Global Template)
        return YayinTipiSablonu::query()
            ->whereKey($yayinTipiId)
            ->value('ad')
            ?? throw new \InvalidArgumentException("Invalid yayin_tipi_id: {$yayinTipiId}");
    }
}
