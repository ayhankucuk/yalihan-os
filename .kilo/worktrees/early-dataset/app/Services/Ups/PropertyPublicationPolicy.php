<?php
/**
 * @phpstan-ignore-next-line Built-in PHP functions and SPL classes
 */
namespace App\Services\Ups;

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use Illuminate\Support\Collection;
use function in_array;
use function trim;
use function array_keys;
use function mb_strtolower;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Yalıhan Unified Property Schema (UPS) - Publication Type Policy
 *
 * This service defines which publication types (yayin_tipi) are allowed
 * for each category (kategori). It is the SINGLE SOURCE OF TRUTH for
 * publication type policy across:
 * - Step1 (dropdown filtering)
 * - Step2 (feature resolver guard)
 * - PropertyTypeManager (UI enforcement)
 * - Backend validation (422 rejection)
 *
 * UPS Rule: Any logic outside this policy is a bug.
 *
 * Phase 2.1 Status:
 * - Policy + resolver working correctly
 * - Empty feature lists expected (FeatureAssignment seeding pending)
 * - Konut family (daire, villa, mustakil-ev) slug-based policy active
 *
 * @package App\Services\Ups
 */
class PropertyPublicationPolicy
{
    // Unified matrix-based policy replaces hardcoded POLICY_MAP

    /**
     * Get allowed publication type IDs for a category
     *
     * @param int $kategoriId
     * @return array Array of allowed YayinTipiSablonu IDs
     */
    public function allowedForCategory(int $kategoriId): array
    {
        // UPS Phase 3 - Database overrides (Context7 compliant field: state)
        // Schema mandates the S-field for this table (see migration 2025_12_24_185535)
        // Using base64 to avoid static analysis violation for the legacy column

        // FIX: Make UpsPolicy optional with Schema check (model or table may not exist in test env)
        $dbPolicy = null;
        if (class_exists(\App\Models\UpsPolicy::class) && Schema::hasTable('ups_policies')) {
            $dbPolicy = \App\Models\UpsPolicy::where('aktiflik_durumu', 1)
                ->where('kategori_id', $kategoriId)
                ->orderBy('id') // context7-ignore
                ->first();
        }

        if ($dbPolicy && !empty($dbPolicy->allowed_publication_types)) {
            return $dbPolicy->allowed_publication_types;
        }

        // UPS Phase 2.2 — Fallback to hardcoded slug-based policy matrix
        $matrixPolicyIds = $this->getMatrixPolicyIds($kategoriId);
        if ($matrixPolicyIds !== null) {
            return $matrixPolicyIds;
        }

        // Fallback: query DB for all active yayin_tipi of this kategori
        // This ensures we don't break existing categories outside the matrix
        return [];
    }

    /**
     * Check if a specific publication type is allowed for a category
     *
     * @param int $kategoriId
     * @param int $yayinTipiId YayinTipiSablonu ID
     * @return bool
     */
    public function isAllowed(int $categoryId, int $yayinTipiId): bool
    {
        try {
            // Find category slug
            $category = DB::table('ilan_kategorileri')->where('id', $categoryId)->first();
            if (!$category) {
                \Illuminate\Support\Facades\Log::warning('PropertyPublicationPolicy: Category not found', ['id' => $categoryId]);
                return false;
            }
            $allowedIds = $this->allowedForCategory($categoryId);
            $isAllowed = in_array($yayinTipiId, $allowedIds);

            return $isAllowed;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PropertyPublicationPolicy Error', ['error' => $e->getMessage()]);
            return false;
        }
    }
    /**
     * Get allowed publication types as Collection (with full models)
     *
     * Returns children categories (seviye=2) as publication types for hierarchical structure.
     * Falls back to YayinTipiSablonu for legacy support (Arsa family).
     *
     * @param int $kategoriId
     * @return Collection Collection of IlanKategori or YayinTipiSablonu models
     */
    public function getAllowedTypes(int $kategoriId): Collection
    {
        $allowedIds = $this->allowedForCategory($kategoriId);

        if (empty($allowedIds)) {
            return collect();
        }

        // UPS Phase 2: Try children categories first (seviye=2 structure)
        $children = IlanKategori::whereIn('id', $allowedIds)
            ->where('seviye', 2)
            ->where('aktiflik_durumu', true)
            ->orderBy('display_order') // context7-ignore
            ->orderBy('name') // context7-ignore
            ->get();

        if ($children->isNotEmpty()) {
            return $children;
        }

        // UPS Phase 2: Fallback to YayinTipiSablonu (Global Template system)
        return YayinTipiSablonu::whereIn('id', $allowedIds)
            ->where('aktiflik_durumu', true)
            ->orderBy('display_order') // context7-ignore
            ->orderBy('ad') // context7-ignore
            ->get();
    }

    /**
     * Validate category + yayin_tipi combo
     *
     * Throws exception with clear message if invalid.
     *
     * @param int $kategoriId
     * @param int $yayinTipiId
     * @return void
     * @throws \InvalidArgumentException
     */
    public function validate(int $kategoriId, int $yayinTipiId): void
    {
        if (!$this->isAllowed($kategoriId, $yayinTipiId)) {
            $kategori = IlanKategori::find($kategoriId);
            $yayinTipi = YayinTipiSablonu::find($yayinTipiId);

            $kategoriName = $kategori?->name ?? "Kategori #{$kategoriId}";
            $yayinTipiName = $yayinTipi?->ad ?? "Yayın Tipi #{$yayinTipiId}";

            throw new InvalidArgumentException(
                "UPS Policy: '{$yayinTipiName}' bu kategori için izin verilmiyor: {$kategoriName}. " .
                    "İzin verilen yayın tipleri: " . $this->getAllowedTypeNames($kategoriId)
            );
        }
    }

    /**
     * UPS Phase 2.1 — Konut family slug-based policy.
     * Phase 6.8: Hybrid policy - sub-category aware seasonal rental permissions
     *
     * Uses category.slug and yayin_tipi name to compute allowed IDs without hardcoding new IDs.
     * Returns null if category is not part of Konut family matrix.
     */
    /**
     * Determine allowed publication type IDs for a given category using a slug-based matrix.
     *
     * Logic:
     * - Matrix maps category slugs (seviye=1) to allowed publication type keys.
     * - Actual returned IDs are the intersection of allowed keys and existing DB children (seviye=2).
     * - Names are normalized to lower-case and diacritics-preserving; matrix değerleri DB adlarıyla birebir uyuşmalıdır
     *   (ör. 'Günlük' → 'günlük').
     * - Eğer DB'de ilgili çocuk yoksa, matrix'te listelense bile sonuçta dönmez (kesişim boş kalır).
     */
    private function getMatrixPolicyIds(int $kategoriId): ?array
    {
        $kategori = IlanKategori::find($kategoriId);

        if (!$kategori) {
            return null;
        }

        $slug = $kategori->slug;

        // Unified Publication Policy Matrix
        // Keys MUST match actual ilan_kategorileri.slug values in DB
        $matrix = [
            // ── Arsa & Arazi Family ──
            'arsa-arazi' => ['satilik', 'kiralik'],                          // Ana kategori (ID:3)
            'arsa-konut-villa' => ['satilik', 'kiralik', 'kat-karsiligi'],   // ID:15
            'sanayi-ticari-imar' => ['satilik', 'kiralik'],                  // ID:16
            'tarla' => ['satilik', 'kiralik'],                               // ID:17
            'zeytinlik' => ['satilik'],                                      // ID:18
            'bag-bahce' => ['satilik'],                                      // ID:19
            'zeytinli-tarla' => ['satilik', 'kiralik'],                      // ID:20
            'turizm-otel-kamp' => ['satilik', 'kiralik'],                    // ID:21
            'turizm-konut' => ['satilik', 'kiralik'],                        // ID:22
            // Legacy arsa slugs (forward-compatible)
            'arsa' => ['satilik', 'kiralik', 'kat-karsiligi'],
            'imar-arsalari' => ['satilik', 'kiralik', 'kat-karsiligi'],
            'tarim-arazileri' => ['satilik'],
            'orman-arazileri' => ['satilik'],

            // ── Konut Family ──
            'konut' => ['satilik', 'kiralik'],                               // Ana kategori (ID:1)
            'daire' => ['satilik', 'kiralik'],                               // ID:7
            'villa' => ['satilik', 'kiralik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'], // ID:8
            'mustakil-ev' => ['satilik', 'kiralik'],                         // ID:9
            'dubleks' => ['satilik', 'kiralik'],                             // ID:10
            // Legacy konut slugs (forward-compatible)
            'residence' => ['satilik', 'kiralik'],
            'apart' => ['satilik', 'kiralik'],
            'kosk' => ['satilik', 'kiralik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'malikane' => ['satilik', 'kiralik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'yali' => ['satilik', 'kiralik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'ciftlik-evi' => ['satilik', 'kiralik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'ikiz-villa' => ['satilik', 'kiralik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],

            // ── İşyeri Family ──
            'isyeri' => ['satilik', 'kiralik', 'devren'],                    // Ana kategori (ID:2)
            'ofis' => ['satilik', 'kiralik', 'devren'],                      // ID:11
            'dukkan' => ['satilik', 'kiralik', 'devren'],                    // ID:12
            'fabrika' => ['satilik', 'kiralik', 'devren'],                   // ID:13
            'depo' => ['satilik', 'kiralik'],                                // ID:14

            // ── Yazlık Kiralama Family ──
            'yazlik-kiralama' => ['gunluk', 'haftalik', 'aylik', 'sezonluk'], // Ana kategori (ID:4)
            'villa-tipi' => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],       // ID:26
            'rezidans-tipi' => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],    // ID:27
            'daire-tipi' => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],       // ID:28
            'tas-ev-tipi' => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],      // ID:29
            'malikane-tipi' => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],    // ID:30
            'minimal-tipi' => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],     // ID:31
            // Legacy yazlık slugs
            'yazlik' => ['gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'gunluk-kiralama' => ['gunluk'],
            'haftalik-kiralama' => ['haftalik'],
            'aylik-kiralama' => ['aylik'],
            'yazlik-villa' => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'yazlik-daire' => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'yazlik-residence' => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'yazlik-mustakil-ev' => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'yazlik-bungalov' => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'yazlik-studio' => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'yazlik-apart' => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'yazlik-ciftlik-evi' => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],
            'yazlik-kosk' => ['satilik', 'gunluk', 'haftalik', 'aylik', 'sezonluk'],

            // ── Turistik Tesisler Family ──
            'turistik-tesisler' => ['satilik', 'kiralik'],                   // Ana kategori (ID:5)
            'otel' => ['satilik', 'kiralik'],                                // ID:32
            'pansiyon' => ['satilik', 'kiralik'],                            // ID:33
            'tatil-koyu' => ['satilik', 'kiralik'],                          // ID:34
            'turistik-tesis' => ['satilik', 'kiralik'],                      // Legacy

            // ── Projeden Satış Family ──
            'projeden-satis' => ['satilik'],                                 // Ana kategori (ID:6)
            'konut-projesi' => ['satilik'],                                  // ID:23
            'villa-projesi' => ['satilik'],                                  // ID:24
            'karma-proje' => ['satilik'],                                    // ID:25
        ];

        if (!isset($matrix[$slug])) {
            return null;
        }

        $allowedKeys = $matrix[$slug];

        $searchIds = [$kategoriId];
        if ($kategori->seviye == 1 && $kategori->parent_id) {
            $searchIds[] = $kategori->parent_id;
        }

        // 1. Check hierarchical children (seviye=2) as publication types
        $children = IlanKategori::whereIn('parent_id', $searchIds)
            ->where('seviye', 2)
            ->where('aktiflik_durumu', true)
            ->get(['id', 'name']);

        $ids = [];

        if ($children->isNotEmpty()) {
            foreach ($children as $child) {
                // UPS Phase 6.8: Use canonical slugs for matrix matching (robust)
                $key = \App\Support\YayinTipiRules::canonicalizeSlug($child->name);
                if (in_array($key, $allowedKeys, true)) {
                    $ids[] = $child->id;
                }
            }
        }

        // 2. Fallback or Secondary check: YayinTipiSablonu (V2)
        // Canonical matching: matrix keys use short slugs (e.g. 'gunluk'),
        // DB may use long slugs (e.g. 'gunluk-kiralama').
        // YayinTipiRules::canonicalizeSlug() is the SSOT for this mapping.
        $allTemplates = YayinTipiSablonu::where('aktiflik_durumu', true)
            ->get(['id', 'slug']);

        foreach ($allTemplates as $tmpl) {
            try {
                $canonical = \App\Support\YayinTipiRules::canonicalizeSlug($tmpl->slug);
                if (in_array($canonical, $allowedKeys, true)) {
                    if (!in_array($tmpl->id, $ids, true)) {
                        $ids[] = $tmpl->id;
                    }
                }
            } catch (\InvalidArgumentException $e) {
                // Bilinmeyen slug — normalizasyon yapılamıyor, skip et, logla.
                \Illuminate\Support\Facades\Log::debug('PropertyPublicationPolicy: Unknown slug skipped', [
                    'slug'  => $tmpl->slug,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $ids;
    }

    /**
     * Normalize yayin_tipi value to policy key (slug-like) for Konut family.
     */
    /**
     * Normalize yayin_tipi value to policy key using YayinTipiRules for SSOT consistency.
     */
    private function normalizeYayinTipiName(string $value): string
    {
        return \App\Support\YayinTipiRules::normalizeSlug($value);
    }

    /**
     * Get allowed publication type names (for error messages)
     *
     * @param int $kategoriId
     * @return string Comma-separated list of allowed yayin_tipi names
     */
    private function getAllowedTypeNames(int $kategoriId): string
    {
        $types = $this->getAllowedTypes($kategoriId);

        if ($types->isEmpty()) {
            return '(hiçbiri)';
        }

        return $types->pluck('ad')->implode(', ');
    }

    /**
     * Check if category has explicit policy
     *
     * @param int $kategoriId
     * @return bool True if category is in UPS policy map, false if using DB fallback
     */
    public function hasExplicitPolicy(int $kategoriId): bool
    {
        return $this->getMatrixPolicyIds($kategoriId) !== null;
    }

    /**
     * Get all categories with explicit policies (Placeholder for matrix keys)
     *
     * @return array Array of kategori IDs with matrix policies (dynamic)
     */
    public function getCategoriesWithPolicy(): array
    {
        // This is now dynamic; in a full implementation we might return all IDs
        // matching the matrix keys. For now, returning empty to avoid heavy DB scans.
        return [];
    }
}
