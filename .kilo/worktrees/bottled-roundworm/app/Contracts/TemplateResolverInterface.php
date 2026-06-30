<?php

namespace App\Contracts;

use App\Models\YayinTipiSablonu;

/**
 * Template Resolver Interface (V2)
 *
 * PRIMARY PATH: resolveByJunction (junction-first - SAB Kural 2)
 * LEGACY PATH:  resolve (kategori_id + yayin_tipi string)
 *
 * @see docs/adr/2026-02-22-junction-first-resolver.md
 */
interface TemplateResolverInterface
{
    /**
     * PRIMARY: Junction-first template cozumleme.
     *
     * SAB Kural 2: Birincil SoT = Junction (yayin_tipi_sablonu_id).
     * SAB Kural 3: requestKategoriId verilmisse eslesmeli, yoksa FAIL-FAST.
     * SAB Kural 4: Deterministic - orderBy id zorunlu.
     *
     * @throws \App\Exceptions\TemplateNotFoundException
     * @throws \App\Exceptions\TemplateCategoryMismatchException
     * @throws \InvalidArgumentException
     */
    public function resolveByJunction(int $junctionId, ?int $requestKategoriId = null): YayinTipiSablonu;

    /**
     * Junction cache temizle.
     */
    public function clearJunctionCache(int $junctionId, ?int $kategoriId = null): void;

    /**
     * LEGACY: slug/ad bazli cozumleme.
     * @deprecated resolveByJunction() kullanin.
     * @throws \App\Exceptions\TemplateNotFoundException
     * @throws \InvalidArgumentException
     */
    public function resolve(int $kategoriId, string $yayinTipi): ?YayinTipiSablonu;

    public function exists(int $kategoriId, string $yayinTipi): bool;

    public function getTemplatesForCategory(int $kategoriId);
}
