<?php

namespace App\Services\PropertyHub;

use App\Exceptions\PropertyHub\TemplateResolutionException;
use App\Models\AltKategoriYayinTipi;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use Illuminate\Support\Facades\Log;

/**
 * Template Context Resolver — Single Source of Truth
 *
 * Resolves (altKategoriId, yayinTipiId) pair into everything the AI generator needs:
 * - Pivot (junction) record
 * - Kategori + parent
 * - Normalized UPS strings
 *
 * Throws TemplateResolutionException with baked-in HTTP durum_kodu + contract code
 * so the controller never needs to guess which error code to return.
 *
 * @see contracts/ai-generate-template-v1.json
 * @see docs/adr/2026-02-15-api-contract-freeze.md
 */
class TemplateContextResolver
{
    /**
     * UPS normalization: Turkish → ASCII-safe yayın tipi names
     */
    private const YAYIN_TIPI_MAP = [
        'Satılık' => 'Satilik',
        'Günlük Kiralama' => 'Gunluk Kiralama',
        'Kiralık' => 'Kiralik',
    ];

    /**
     * UPS normalization: Alt tür canonical mapping
     */
    private const ALT_TUR_MAP = [
        'Daire' => 'Daire',
        'Villa' => 'Villa',
        'Tarla' => 'Tarla',
    ];

    /**
     * Resolve template context from (altKategoriId, yayinTipiId) pair.
     *
     * @param int $altKategoriId  ilan_kategorileri.id (body param)
     * @param int $yayinTipiId    yayin_tipi_sablonlari.id (URL param)
     * @return TemplateContext Resolved context DTO
     *
     * @throws TemplateResolutionException PIVOT_NOT_FOUND|DATA_INTEGRITY
     */
    public function resolve(int $altKategoriId, int $yayinTipiId): TemplateContext
    {
        // 1. Pivot (junction) var mı? — exact (alt_kategori_id, yayin_tipi_id) pair
        $junction = AltKategoriYayinTipi::where('alt_kategori_id', $altKategoriId)
            ->where('yayin_tipi_id', $yayinTipiId)
            ->where('aktiflik_durumu', true)
            ->first();

        if (!$junction) {
            throw new TemplateResolutionException(
                contractCode: 'PIVOT_NOT_FOUND',
                message: 'Bu alt kategori ve yayın tipi için aktif bir eşleşme bulunamadı.',
                httpStatus: 422,
                debug: ['alt_kategori_id' => $altKategoriId, 'yayin_tipi_id' => $yayinTipiId],
            );
        }

        // 2. Kategori var mı? (data integrity — junction orphan check)
        $kategori = IlanKategori::find($altKategoriId);

        if (!$kategori) {
            Log::error('template_resolution_data_integrity', [
                'yayin_tipi_id' => $yayinTipiId,
                'alt_kategori_id' => $altKategoriId,
                'hata_mesaji' => 'Junction kaydı mevcut ancak bağlı IlanKategori bulunamadı',
            ]);

            throw new TemplateResolutionException(
                contractCode: 'DATA_INTEGRITY',
                message: 'Veri bütünlüğü hatası: Bağlı alt kategori bulunamadı (ID: ' . $altKategoriId . ')',
                httpStatus: 500,
                debug: ['yayin_tipi_id' => $yayinTipiId, 'alt_kategori_id' => $altKategoriId],
            );
        }

        // 3. YayinTipi template for normalization
        $template = YayinTipiSablonu::find($yayinTipiId);

        // 4. Parent + normalizasyon
        $parent = $kategori->parent;

        $rawKategori = $parent ? $parent->name : $kategori->name;
        $rawYayinTipi = $template ? $template->name : '';
        $rawAltTur = $parent ? $kategori->name : ($template->alt_kategori_name ?? ($template ? $template->name : ''));

        return new TemplateContext(
            template: $template,
            junction: $junction,
            kategori: $kategori,
            parentKategori: $parent,
            normalizedKategori: str_replace(' & ', ' & ', $rawKategori),
            normalizedYayinTipi: self::YAYIN_TIPI_MAP[$rawYayinTipi] ?? $rawYayinTipi,
            normalizedAltTur: self::ALT_TUR_MAP[$rawAltTur] ?? $rawAltTur,
        );
    }
}
