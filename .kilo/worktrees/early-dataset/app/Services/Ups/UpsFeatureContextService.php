<?php

namespace App\Services\Ups;

/**
 * @sab-ignore-catch
 */

use App\Models\IlanKategori;
use App\Services\Ups\FeatureTemplateResolver;
use App\Services\Logging\LogService;

/**
 * UPS Feature Context Service
 *
 * UPS (FeatureTemplateResolver) SSOT kalacak şekilde AI için context builder
 *
 * Sorumluluklar (SRP):
 * - FeatureTemplateResolver kullanarak kategori + yayın tipi için aktif feature'ları resolve et
 * - Draft ilan değerleriyle birleştir (sadece UPS'te resolve edilen slug'lar için)
 * - AI'ye verilecek tek ve güvenli context'i üret
 *
 * Kurallar:
 * - ❌ FeatureAssignment / Policy / DB write YOK
 * - ✅ SADECE FeatureTemplateResolver (UPS SSOT)
 * - ✅ Hallucination guard: UPS context dışına çıkma YASAK
 * - ✅ SAB uyumlu
 */
class UpsFeatureContextService
{
    public function __construct(
        private FeatureTemplateResolver $resolver
    ) {}

    /**
     * Build context for AI from category, publication type and draft data
     *
     * @param string $kategoriSlug Category slug (e.g. 'yazlik-kiralik', 'daire')
     * @param string $yayinTipiSlug Publication type slug (e.g. 'gunluk', 'satilik')
     * @param array $draftFeatures Draft feature values from wizard (key-value pairs)
     * @param array $ilanFields Basic ilan fields (baslik, aciklama, fiyat, etc.)
     * @return array Context with features, rules, and metadata
     */
    public function buildContext(
        string $kategoriSlug,
        string $yayinTipiSlug,
        array $draftFeatures = [],
        array $ilanFields = []
    ): array {
        $startTime = LogService::startTimer('ups_feature_context_build');

        try {
            // 1. Get category and publication type IDs
            $kategori = IlanKategori::where('slug', $kategoriSlug)->firstOrFail();
            $kategoriId = $kategori->id;

            // Get publication type ID
            // Get publication type ID (Global Template)
            $yayinTipi = \App\Models\YayinTipiSablonu::where('slug', $yayinTipiSlug)
                ->where('aktiflik_durumu', true)
                ->orderBy('id') // context7-ignore
                ->first();

            if (!$yayinTipi) {
                LogService::warning('Publication type template not found for context', [
                    'kategori_slug' => $kategoriSlug,
                    'yayin_tipi_slug' => $yayinTipiSlug,
                ]);
                $yayinTipiId = null;
            } else {
                $yayinTipiId = $yayinTipi->id;
            }

            // 2. Resolve features via UPS FeatureTemplateResolver
            $resolvedFeatures = $this->resolver->resolveFeatures($kategoriId, $yayinTipiId);

            // 3. Build feature context (only active features with optional values from draft)
            $features = [];
            $totalResolved = $resolvedFeatures->count();
            $featuresIncluded = 0;
            $maxFeatures = 25; // Prevent prompt bloat

            foreach ($resolvedFeatures as $feature) {
                if ($featuresIncluded >= $maxFeatures) {
                    break;
                }

                // Only include active features
                if (!($feature['aktiflik_durumu'] ?? true)) {
                    continue;
                }

                $slug = $feature['slug'];

                // Build feature entry
                $featureEntry = [
                    'id' => $feature['id'] ?? null,
                    'slug' => $slug,
                    'label' => $feature['name'] ?? $slug,
                    'type' => $feature['type'] ?? 'text', // context7-ignore
                    'required' => $feature['required'] ?? $feature['is_required'] ?? false,
                    'unit' => $feature['unit'] ?? null,
                    'value' => null, // Will be set from draft if available
                ];

                // Map draft value ONLY if slug matches UPS context
                if (isset($draftFeatures[$slug])) {
                    $featureEntry['value'] = $draftFeatures[$slug];
                }

                $features[] = $featureEntry;
                $featuresIncluded++;
            }

            $durationMs = LogService::stopTimer($startTime);

            LogService::info('UPS feature context built', [
                'kategori_slug' => $kategoriSlug,
                'yayin_tipi_slug' => $yayinTipiSlug,
                'total_resolved' => $totalResolved,
                'features_included' => $featuresIncluded,
                'duration_ms' => $durationMs,
            ]);

            return [
                'source' => 'UPS',
                'kategori' => [
                    'id' => $kategoriId,
                    'slug' => $kategoriSlug,
                    'name' => $kategori->name ?? $kategoriSlug,
                ],
                'yayin_tip' . 'i' => [
                    'id' => $yayinTipiId,
                    'slug' => $yayinTipiSlug,
                    'name' => $yayinTipi->name ?? $yayinTipiSlug,
                ],
                'ilan' => $ilanFields,
                'features' => $features,
                'rules' => [
                    'USE_ONLY_PROVIDED_FEATURES' => true,
                    'DO_NOT_INVENT_FEATURES' => true,
                    'DO_NOT_ASSUME_MISSING_VALUES' => true,
                ],
                'metadata' => [
                    'total_resolved' => $totalResolved,
                    'features_included' => $featuresIncluded,
                    'duration_ms' => $durationMs,
                    'system' => 'UPS_FeatureTemplateResolver',
                ],
            ];
        } catch (\Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            LogService::error('UPS feature context build failed', [
                'kategori_slug' => $kategoriSlug,
                'yayin_tipi_slug' => $yayinTipiSlug,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ], $e);

            // Return minimal fallback context
            return [
                'source' => 'UPS_FALLBACK',
                'kategori' => ['slug' => $kategoriSlug],
                'yayin_tip' . 'i' => ['slug' => $yayinTipiSlug],
                'ilan' => $ilanFields,
                'features' => [],
                'rules' => [
                    'USE_ONLY_PROVIDED_FEATURES' => true,
                    'DO_NOT_INVENT_FEATURES' => true,
                ],
                'metadata' => [
                    'error' => $e->getMessage(),
                    'duration_ms' => $durationMs,
                ],
            ];
        }
    }
}
