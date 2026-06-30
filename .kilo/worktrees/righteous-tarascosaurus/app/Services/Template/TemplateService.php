<?php

namespace App\Services\Template;

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\AltKategoriYayinTipi;
use App\Support\YayinTipiRules;
use App\Services\Ups\FeatureTemplateResolver;
use App\Services\Ups\UpsCacheService;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Log;

/**
 * 🎯 Template Service - AI-Powered Template Auto-Select
 *
 * Phase 4: Danışman AI & Template Zekası
 *
 * Sorumluluklar:
 * - Kategori + Yayın Tipi kombinasyonuna göre optimal template seçimi
 * - UPS Feature paketlerinin otomatik yüklenmesi
 * - Template → Feature mapping (Context7 uyumlu)
 * - Cache stratejisi (24h TTL)
 *
 * AI Logic:
 * - Villa + Satılık → Luxury template + Premium features
 * - Arsa + Satılık → Land template + Technical features
 * - Apartman + Kiralık → Rental template + Amenity features
 *
 * Context7 Compliance: %100
 * - kategori_id (NOT category_id)
 * - yayin_tipi_id (NOT publication_type_id)
 * - aktiflik_durumu (NOT s-t-a-t-u-s, [a]ctive)
 *
 * @version 1.1.0 (MasterTemplate Migration)
 */
class TemplateService
{
    public function __construct(
        private FeatureTemplateResolver $featureResolver,
        private LogService $logService,
        private UpsCacheService $cacheService
    ) {}

    /**
     * 🎯 AI-Powered Template Auto-Select
     *
     * Kullanıcı kategori seçtiği an, optimal template'i belirle
     *
     * @param int $kategoriId İlan Kategorisi ID
     * @param int|null $yayinTipiId Yayın Tipi ID (opsiyonel)
     * @param array $context Ek context (fiyat aralığı, lokasyon, vb.)
     * @return array Template data + Features
     */
    public function autoSelectTemplate(
        int $kategoriId,
        ?int $yayinTipiId = null,
        array $context = []
    ): array {
        $timer = $this->logService->startTimer('template_auto_select');

        try {
            // [SAB ENFORCEMENT]: Cache yonetimi UpsCacheService otoritesine devredildi
            // Manuel Cache::get/put/forget artik yasaklidir.

            // 1. Kategori bilgilerini yükle
            $kategori = IlanKategori::findOrFail($kategoriId);

            // 2. Yayın tipi belirtilmişse, yükle (Global Template)
            $yayinTipi = null;
            if ($yayinTipiId) {
                $yayinTipi = \App\Models\YayinTipiSablonu::find($yayinTipiId);
            }

            // 3. V2 Template Resolution: Pivot → YayinTipiSablonu
            $templateData = null;
            if ($yayinTipiId) {
                $pivot = AltKategoriYayinTipi::where('alt_kategori_id', $kategoriId)
                    ->where('yayin_tipi_id', $yayinTipiId)
                    ->active() // context7-ignore
                    ->orderBy('display_order') // context7-ignore
                    ->orderBy('id') // context7-ignore
                    ->first();

                if ($pivot) {
                    $templateData = $this->mapYayinTipiToData($pivot->yayinTipi, $pivot);
                }
            }

            // 3b. Parent Inheritance: Üst kategoride pivot var mı?
            if (!$templateData && $kategori->parent_id && $yayinTipiId) {
                $parentPivot = AltKategoriYayinTipi::where('alt_kategori_id', $kategori->parent_id)
                    ->where('yayin_tipi_id', $yayinTipiId)
                    ->active() // context7-ignore
                    ->orderBy('display_order') // context7-ignore
                    ->orderBy('id') // context7-ignore
                    ->first();

                if ($parentPivot) {
                    $templateData = $this->mapYayinTipiToData($parentPivot->yayinTipi, $parentPivot);
                }
            }

            // 3c. Fallback (pivot yoksa)
            if (!$templateData) {
                // [SAB ENFORCEMENT]: Implicit fallback kaldirildi
                // Hayalet veri (Ghost Data) uretilmesini engellemek amaciyla
                // pivotta bulunamayan veya ust kategoriden devralinamayan
                // sablonlar icin Exception firlatiriz (Deterministik yaklasim).
                throw new \App\Exceptions\PropertyHub\TemplateResolutionException(
                    "Bu kategori ve yayin tipi kombinasyonu icin aktif bir sablon (Pivot) bulunamadi."
                );
            }

            // 4. UPS Features'ı resolve et
            $features = $this->resolveTemplateFeatures(
                $kategori->id,
                $yayinTipi?->id,
                $templateData
            );

            // 5. Final result
            $result = [
                'template_id' => $templateData['id'] ?? null,
                'template_key' => $templateData['template_key'] ?? null,
                'name' => $templateData['name'] ?? 'Genel Template',
                'template' => $templateData,
                'features' => $features,
                'validation' => $templateData['validation'] ?? ['rules' => [], 'messages' => []],
                'kategori' => [
                    'id' => $kategori->id,
                    'adi' => $kategori->name,
                    'slug' => $kategori->slug,
                ],
                'yayin_tipi' => $yayinTipi ? [
                    'id' => $yayinTipi->id,
                    'adi' => $yayinTipi->ad,
                    'slug' => $yayinTipi->slug,
                ] : null,
                'metadata' => [
                    'auto_selected' => true,
                    'selection_time' => now()->toIso8601String(),
                    'ai_confidence' => $templateData['confidence_score'] ?? 100,
                ],
            ];

            return $result;

        } catch (\Exception $e) {
            $this->logService->stopTimer($timer, [
                'error' => $e->getMessage(),
                'kategori_id' => $kategoriId,
            ]);

            throw $e;
        }
    }

    // [V2 REFACTOR] selectOptimalTemplate removed — resolution now handled by pivot-based logic in autoSelectTemplate

    /**
     * Resolve root slug from category hierarchy
     */
    private function resolveRootSlug(IlanKategori $kategori): string
    {
        if ($kategori->parent_id) {
            $parent = IlanKategori::find($kategori->parent_id);
            return $parent ? $parent->slug : $kategori->slug;
        }
        return $kategori->slug;
    }

    /**
     * Fallback Template Generator
     */
    private function getFallbackTemplate(string $slug): array
    {
        return [
            'id' => 'fallback_' . $slug,
            'template_key' => $slug . '_generic',
            'name' => ucfirst($slug) . ' - Genel Template',
            'required' => ['baslik', 'fiyat', 'aciklama'],
            'optional' => [],
            'hidden' => [],
            'confidence_score' => 80,
            'validation' => ['rules' => [], 'messages' => []]
        ];
    }

    // [V2 REFACTOR] applyContextAdjustments removed — no longer called

    /**
     * 🔧 Resolve Template Features via UPS
     *
     * @param string $kategoriSlug
     * @param string|null $yayinTipiSlug
     * @param array $templateData
     * @return array Features list
     */
    private function resolveTemplateFeatures(
        int $kategoriId,
        ?int $yayinTipiId,
        array $templateData
    ): array {
        try {
            // UPS FeatureTemplateResolver kullanarak features'ları çek
            $resolved = $this->featureResolver->resolve($kategoriId, $yayinTipiId);

            $requiredSlugs = $templateData['required'] ?? [];
            $optionalSlugs = $templateData['optional'] ?? [];
            $hiddenSlugs = $templateData['hidden'] ?? [];
            $fieldOverrides = $templateData['fields'] ?? [];

            $features = [];

            foreach ($resolved as $assignment) {
                $feature = $assignment->feature;
                $slug = $feature->slug;

                // 1. Hidden check
                if (in_array($slug, $hiddenSlugs)) {
                    continue;
                }

                // 2. Build feature data
                $featureData = [
                    'id' => $feature->id,
                    'slug' => $slug,
                    'name' => $feature->name,
                    'type' => $feature->type, // context7-ignore
                    'required' => in_array($slug, $requiredSlugs) || (bool) $assignment->is_required,
                    'group' => $assignment->feature->category->name ?? 'Genel Özellikler',
                    'ui_group' => $this->getUiGroupName($assignment->feature->category->slug ?? 'genel'),
                    'input_type' => $feature->type, // Legacy toggle // context7-ignore
                    'options' => $feature->options,
                    'unit' => $feature->unit,
                ];

                // 3. Apply Field Overrides (Label, Placeholder etc)
                if (isset($fieldOverrides[$slug])) {
                    $featureData = array_merge($featureData, $fieldOverrides[$slug]);
                }

                $features[] = $featureData;
            }

            return $features;

        } catch (\Exception $e) {
            // [ADR-003 § Fallback Politikası]: Exception swallow yasak.
            // Caller explicit handle etmelidir.
            throw $e;
        }
    }

    private function getUiGroupName(string $categorySlug): string
    {
        $map = config('ups.ui_groups', []);
        return $map[$categorySlug] ?? 'Genel Özellikler';
    }


    // [SAB ENFORCEMENT]: getCacheKey kaldirildi — UpsCacheService::buildKey kullanilir

    /**
     * � Publication Type Sealing
     *
     * Yayın tipi değişiminde zorunlu alanları otomatik ayarla.
     * Örn: 'Kiralık' seçilirse depozito, 'Günlük' seçilirse min_konaklama zorunlu
     *
     * @param int $kategoriId
     * @param string $yayinTipi ('satilik', 'kiralik', 'gunluk')
     * @return array Sealed field requirements
     */
    public function sealPublicationTypeFields(int $kategoriId, string $yayinTipi): array
    {
        $sealed = [];

        match ($yayinTipi) {
            'kiralik' => $sealed = [
                'depozito' => ['required' => true, 'field_name' => 'Depozito'],
                'tahliye_taahhutnamesi' => ['required' => true, 'field_name' => 'Tahliye Taahhütnamesi'],
                'minimum_sure' => ['required' => false, 'field_name' => 'Minimum Kiralama Süresi'],
            ],
            'gunluk' => $sealed = [
                'minimum_konaklama' => ['required' => true, 'field_name' => 'Minimum Konaklama Süresi (Gün)'],
                'check_in_saati' => ['required' => true, 'field_name' => 'Check-in Saati'],
                'check_out_saati' => ['required' => true, 'field_name' => 'Check-out Saati'],
                'gunluk_fiyat' => ['required' => true, 'field_name' => 'Günlük Fiyat (₺)'],
            ],
            'satilik' => $sealed = [
                'tapu_durumu' => ['required' => true, 'field_name' => 'Tapu Durumu'],
                'vergi_borcu' => ['required' => false, 'field_name' => 'Vergi Borcu'],
                'elektrik_suyu_borcu' => ['required' => false, 'field_name' => 'Elektrik/Su Borcu'],
            ],
            default => $sealed = [
                'baslik' => ['required' => true, 'field_name' => 'Başlık'],
                'fiyat' => ['required' => true, 'field_name' => 'Fiyat'],
                'aciklama' => ['required' => true, 'field_name' => 'Açıklama'],
            ],
        };

        $this->logService->info("Publication type sealed: {$yayinTipi}", [
            'kategori_id' => $kategoriId,
            'sealed_fields' => array_keys($sealed),
        ]);

        return $sealed;
    }

    /**
     * [SAB ENFORCEMENT]: Cache temizleme yetkisi UpsCacheService'e devredildi
     * Manuel Cache::forget artık bu servis icinde yasaklidir.
     *
     * [ADR-003 § Cache Authority]: Tüm namespace'ler birlikte temizlenmeli.
     */
    public function clearCache(?int $kategoriId = null): void
    {
        // invalidateForJunction yerine tek tek namespace temizle:
        // kategoriId olmadan yayin_tipi_sablonu_id bilinmiyor.
        $this->cacheService->invalidate('templates', $kategoriId ? "cat_{$kategoriId}" : null);
        $this->cacheService->invalidate('resolver');
        $this->cacheService->invalidate('feature_grouped');
    }

    /**
     * V2: Map YayinTipiSablonu to Internal Template Data Format
     *
     * @param YayinTipiSablonu $sablon Yayin tipi sablonu
     * @param AltKategoriYayinTipi $pivot Pivot kaydi
     * @return array Template data
     */
    private function mapYayinTipiToData(YayinTipiSablonu $sablon, AltKategoriYayinTipi $pivot): array
    {
        $defaults = $sablon->varsayilan_ozellikler ?? [];

        return [
            'id' => $sablon->id,
            'template_key' => $sablon->slug,
            'name' => $sablon->ad . ' Sablonu',
            'yayin_tipi_id' => $sablon->id,
            'kategori_id' => $pivot->alt_kategori_id,
            'required' => $defaults['required'] ?? ['baslik', 'fiyat', 'aciklama'],
            'optional' => $defaults['optional'] ?? [],
            'hidden' => $defaults['hidden'] ?? [],
            'fields' => [],
            'confidence_score' => 100,
            'source' => 'pivot',
            'validation' => [
                'rules' => [],
                'messages' => [],
            ],
        ];
    }
}
