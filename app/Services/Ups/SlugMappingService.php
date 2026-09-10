<?php

namespace App\Services\Ups;

use App\Models\IlanKategori;
use App\Models\YayinTipi;
use App\Models\YayinTipiSablonu;
use InvalidArgumentException;
use RuntimeException;

/**
 * SlugMappingService — V1 → V2 Canonical Slug Mapping Pipeline
 *
 * Phase 2 — Slug Mapping Kontratı (SAAB 3E: slug-based mapping)
 *
 * Pipeline:
 *   listing_type_id (legacy integer)
 *       ↓
 *   yayin_tipleri.id → yayin_tipleri.slug (V1 slug)
 *       ↓
 *   V1 slug → canonical typeSlug (lookup table)
 *       ↓
 *   sub_category_id → ilan_kategorileri.slug (kategori slug)
 *       ↓
 *   template_slug = "{kategori.slug}-{typeSlug}"
 *       ↓
 *   YayinTipiSablonu::where('slug', template_slug)->first() → template.id
 *       ↓
 *   assignable_id = template.id, assignable_type = YayinTipiSablonu::class
 *
 * @see storage/tmp/canonical-mapping-inheritance-implementation-plan.md §1.1–1.5
 */
class SlugMappingService
{
    /**
     * V1 yayin_tipleri.slug → canonical typeSlug lookup table.
     *
     * V1 slugs are the raw slugs in the yayin_tipleri table (e.g., "gunluk-kiralik").
     * Canonical typeSlugs are the short forms used in template slugs (e.g., "gunluk").
     *
     * @see §1.4 V1 Slug → Canonical TypeSlug Lookup
     */
    private const V1_TO_CANONICAL = [
        'satilik'          => 'satilik',
        'kiralik'          => 'kiralik',
        'gunluk-kiralik'   => 'gunluk',
        'haftalik-kiralik' => 'haftalik',
        'aylik-kiralik'    => 'aylik',
        'sezonluk-kiralik' => 'sezonluk',
        'devren'           => 'devren',
        'kat-karsiligi'    => 'kat-karsiligi',
    ];

    /**
     * listing_type_id → yayin_tipleri.id mapping (1:1, verified).
     *
     * In the current DB, listing_type_id values happen to match yayin_tipleri.id.
     * This is kept explicit for clarity and future-proofing.
     *
     * @see §1.5 Tam Mapping Tablosu
     */
    private const LISTING_TYPE_TO_YAYIN_TIPI_ID = [
        1 => 1, // satilik
        2 => 2, // kiralik
        3 => 3, // kat-karsiligi
        4 => 4, // devren
        5 => 5, // gunluk-kiralik
        6 => 6, // haftalik-kiralik
        7 => 7, // aylik-kiralik
        8 => 8, // sezonluk-kiralik
    ];

    /**
     * Shorten a V1 slug by removing the "-kiralik" or "-kiralama" suffix.
     *
     * Examples:
     *   satilik       → satilik (no change)
     *   kiralik       → kiralik (no change — pattern requires leading "-")
     *   gunluk-kiralik → gunluk
     *   sezonluk-kiralik → sezonluk
     *
     * @see §1.2 shortenSlug Fonksiyonu
     */
    public function shortenSlug(string $slug): string
    {
        return preg_replace('/(-kiralik|-kiralama)$/', '', $slug);
    }

    /**
     * Map a V1 yayin_tipleri.slug to the canonical typeSlug.
     *
     * @param string $v1Slug The slug from the yayin_tipleri table
     * @return string The canonical short typeSlug
     * @throws InvalidArgumentException If the V1 slug is not in the lookup table
     */
    public function v1SlugToCanonical(string $v1Slug): string
    {
        $v1Slug = trim($v1Slug);

        if (!isset(self::V1_TO_CANONICAL[$v1Slug])) {
            throw new InvalidArgumentException(
                "SlugMappingService: Unknown V1 yayin_tipleri slug: '{$v1Slug}'. "
                . 'Expected one of: ' . implode(', ', array_keys(self::V1_TO_CANONICAL))
            );
        }

        return self::V1_TO_CANONICAL[$v1Slug];
    }

    /**
     * Look up the V1 yayin_tipleri.slug by listing_type_id.
     *
     * @param int $listingTypeId The legacy listing_type_id
     * @return string The V1 yayin_tipleri.slug
     * @throws InvalidArgumentException If listing_type_id is not mapped
     */
    public function listingTypeIdToV1Slug(int $listingTypeId): string
    {
        $yayinTipiId = self::LISTING_TYPE_TO_YAYIN_TIPI_ID[$listingTypeId] ?? null;

        if ($yayinTipiId === null) {
            throw new InvalidArgumentException(
                "SlugMappingService: Unknown listing_type_id: {$listingTypeId}. "
                . 'Expected one of: ' . implode(', ', array_keys(self::LISTING_TYPE_TO_YAYIN_TIPI_ID))
            );
        }

        $yayinTipi = YayinTipi::find($yayinTipiId);

        if (!$yayinTipi) {
            throw new RuntimeException(
                "SlugMappingService: yayin_tipleri record not found for id={$yayinTipiId} "
                . "(listing_type_id={$listingTypeId}). The yayin_tipleri table may need seeding."
            );
        }

        return $yayinTipi->slug;
    }

    /**
     * Resolve a YayinTipiSablonu template by kategori + listing_type_id.
     *
     * Full pipeline:
     *   listing_type_id → yayin_tipleri.slug → canonical typeSlug
     *   sub_category_id → ilan_kategorileri.slug
     *   template_slug = "{kategori.slug}-{typeSlug}"
     *   YayinTipiSablonu::where('slug', template_slug)
     *
     * @param int $subCategoryId The ilan_kategorileri.id for the sub-category
     * @param int $listingTypeId The legacy listing_type_id
     * @return YayinTipiSablonu The resolved template
     * @throws InvalidArgumentException If any mapping step fails
     * @throws RuntimeException If the template cannot be found
     */
    public function resolveTemplate(int $subCategoryId, int $listingTypeId): YayinTipiSablonu
    {
        // 1. listing_type_id → V1 slug
        $v1Slug = $this->listingTypeIdToV1Slug($listingTypeId);

        // 2. V1 slug → canonical typeSlug
        $typeSlug = $this->v1SlugToCanonical($v1Slug);

        // 3. sub_category_id → kategori slug
        $kategori = IlanKategori::find($subCategoryId);
        if (!$kategori) {
            throw new RuntimeException(
                "SlugMappingService: ilan_kategorileri record not found for id={$subCategoryId}."
            );
        }

        // 4. Construct template slug
        $templateSlug = $kategori->slug . '-' . $typeSlug;

        // 5. Resolve template
        $template = YayinTipiSablonu::where('slug', $templateSlug)->first();

        if (!$template) {
            throw new RuntimeException(
                "SlugMappingService: YayinTipiSablonu not found for slug='{$templateSlug}' "
                . "(kategori_slug='{$kategori->slug}', typeSlug='{$typeSlug}', "
                . "listing_type_id={$listingTypeId}, sub_category_id={$subCategoryId}). "
                . 'The YayinTipiSeeder may need to be run to provision templates.'
            );
        }

        return $template;
    }

    /**
     * Resolve a YayinTipiSablonu template by kategori slug + listing_type_id.
     *
     * Alternative entry point using kategori slug directly instead of ID.
     *
     * @param string $kategoriSlug The ilan_kategorileri.slug
     * @param int $listingTypeId The legacy listing_type_id
     * @return YayinTipiSablonu The resolved template
     * @throws InvalidArgumentException If any mapping step fails
     * @throws RuntimeException If the template cannot be found
     */
    public function resolveTemplateByKategoriSlug(string $kategoriSlug, int $listingTypeId): YayinTipiSablonu
    {
        // 1. listing_type_id → V1 slug
        $v1Slug = $this->listingTypeIdToV1Slug($listingTypeId);

        // 2. V1 slug → canonical typeSlug
        $typeSlug = $this->v1SlugToCanonical($v1Slug);

        // 3. Construct template slug
        $templateSlug = $kategoriSlug . '-' . $typeSlug;

        // 4. Resolve template
        $template = YayinTipiSablonu::where('slug', $templateSlug)->first();

        if (!$template) {
            throw new RuntimeException(
                "SlugMappingService: YayinTipiSablonu not found for slug='{$templateSlug}' "
                . "(kategori_slug='{$kategoriSlug}', typeSlug='{$typeSlug}', "
                . "listing_type_id={$listingTypeId}). "
                . 'The YayinTipiSeeder may need to be run to provision templates.'
            );
        }

        return $template;
    }

    /**
     * Get the full mapping report for diagnostics.
     *
     * Returns an array describing each mapping step for a given
     * sub_category_id + listing_type_id pair.
     *
     * @param int $subCategoryId
     * @param int $listingTypeId
     * @return array Mapping details
     */
    public function getMappingReport(int $subCategoryId, int $listingTypeId): array
    {
        $report = [
            'listing_type_id' => $listingTypeId,
            'sub_category_id' => $subCategoryId,
        ];

        try {
            $report['yayin_tipi_id'] = self::LISTING_TYPE_TO_YAYIN_TIPI_ID[$listingTypeId] ?? null;
            $report['v1_slug'] = $this->listingTypeIdToV1Slug($listingTypeId);
            $report['canonical_type_slug'] = $this->v1SlugToCanonical($report['v1_slug']);

            $kategori = IlanKategori::find($subCategoryId);
            $report['kategori_slug'] = $kategori?->slug;
            $report['template_slug'] = $report['kategori_slug']
                ? $report['kategori_slug'] . '-' . $report['canonical_type_slug']
                : null;

            $report['template_id'] = null;
            if ($report['template_slug']) {
                $template = YayinTipiSablonu::where('slug', $report['template_slug'])->first();
                $report['template_id'] = $template?->id;
            }

            $report['resolved'] = $report['template_id'] !== null;
        } catch (Throwable $e) {
            $report['error'] = $e->getMessage();
            $report['resolved'] = false;
        }

        return $report;
    }
}
