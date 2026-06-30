<?php

namespace App\Services\AI;

use App\Services\Logging\LogService;
use App\Services\Ups\FeatureTemplateResolver;

/**
 * ��️ SAB SEALED
 * - Forbidden keywords: "st*tus" family (do not introduce)
 * - SSOT: naming must reflect domain semantics (e.g., yayin_durumu vs aktiflik_durumu)
 * - No hidden side-effects: logic stays in service layer, UI is dumb
 * - Any change must pass: bekci:audit + integrity scan
 */
class SmartFieldGenerationService
{
    private const CATEGORY_KONUT = 'konut';
    private const CATEGORY_ARSA = 'arsa';
    private const CATEGORY_ISYERI = 'isyeri';
    private const CATEGORY_YAZLIK = 'yazlik';
    private const YAYIN_SATILIK = 'satilik';
    private const YAYIN_KIRALIK = 'kiralik';

    protected AiWalletService $wallet;
    protected AiPricingService $pricing;

    public function __construct(AiWalletService $wallet, AiPricingService $pricing)
    {
        $this->wallet = $wallet;
        $this->pricing = $pricing;
    }

    /**
     * Generate smart recommendations with confidence policy and explainability
     *
     * [WFC-015] hallucination_protection guard active
     * @param array $suggestions Raw suggestions matched from extractFromText or analyzeImages
     * @param int|null $kategoriId Context for UPS Guard
     * @param int|null $yayinTipiId Context for UPS Guard
     * @return array
     */
    public function generateSmartRecommendations(array $suggestions, ?int $kategoriId = null, ?int $yayinTipiId = null): array
    {
        $t0 = LogService::startTimer('smart_field_recommendations');

        if (config('ai-cost-guard.enabled', true)) {
            $tenantId = config('ai.defaults.tenant_id', 1);
            $price = $this->pricing->getPrice('smart_fields');
            $this->wallet->deductCredits($tenantId, $price, 'smart_field_generation');
        }

        // 1. Enrich with Confidence (from Dictionary/Logic) if not present
        // Note: In this refactor, extractFromText providers structured data directly.
        // We ensure structure integrity here.

        $processed = [];
        $dict = $this->getDictionary();

        // 2. Map pure slugs to structured items if legacy input
        foreach ($suggestions as $key => $item) {
            if (is_string($item)) {
                // Feature lookup in dictionary
                $dictInfo = $this->findInDictionary($item, $dict);
                $processed[] = [
                    'slug' => $item,
                    'confidence' => $dictInfo['confidence'] ?? 0.5,
                    'source' => 'unknown',
                    'reason' => 'Manuel/Legacy input',
                    'source_reference' => $item
                ];
            } else {
                // Already structured feature from extractFromText
                $processed[] = $item;
            }
        }

        // 3. UPS Guard (Filter by allowed features for this category/publication)
        if ($kategoriId && $yayinTipiId) {
            $processed = $this->applyUpsGuard($processed, $kategoriId, $yayinTipiId);
        }

        // 4. Apply Confidence Policy
        $finalResults = $this->applyConfidencePolicy($processed, $kategoriId, $yayinTipiId);

        LogService::info('smart_field_recommendations_done', [
            'input_count' => count($suggestions),
            'output_count' => count($finalResults),
            'duration_ms' => (int) LogService::stopTimer($t0),
        ]);

        return $finalResults;
    }

    /**
     * Apply Confidence Governance Policy (Phase 7 & 9)
     *
     * Dynamically adjusted thresholds + config-based fallbacks + forbidden list
     */
    private function applyConfidencePolicy(array $items, ?int $categoryId = null, ?int $yayinTipiId = null): array
    {
        $filtered = [];

        // Phase 9: Load Adaptive Thresholds
        /** @var AdaptiveThresholdEngine $thresholdEngine */
        $thresholdEngine = app(AdaptiveThresholdEngine::class);
        $thresholds = $thresholdEngine->getActiveThresholds($categoryId, $yayinTipiId);

        $autoThreshold = $thresholds['auto_apply'];
        $suggestThreshold = $thresholds['suggest'];

        $forbiddenAutoApply = config('ai-governance.forbidden_auto_apply', []);

        foreach ($items as $item) {
            $conf = (float) ($item['confidence'] ?? 0);
            $slug = $item['slug'] ?? '';

            if ($conf < $suggestThreshold) {
                continue; // Reject low confidence
            }

            // Check forbidden auto-apply list
            $isForbidden = in_array($slug, $forbiddenAutoApply);

            // Add threshold info to explainability
            $item['explainability_detail']['threshold_used'] = $thresholds;

            // Phase 9: Detailed Reasoning (Explainability v2)
            $reason = ($conf >= $autoThreshold)
                ? "Güven skoru ({$conf}), dinamik eşik değerini ({$autoThreshold}) aştığı için otomatik uygulandı."
                : "Güven skoru ({$conf}), öneri eşiğini ({$suggestThreshold}) aştığı için öneri olarak sunuldu.";

            if ($isForbidden) {
                $reason = "Güven skoru yüksek ({$conf}) ancak bu özellik otomatik uygulama yasaklı listesinde.";
            }

            $item['explainability_v2'] = [
                'primary_reason' => $reason,
                'threshold_context' => [
                    'auto' => $autoThreshold,
                    'suggest' => $suggestThreshold,
                    'is_adaptive' => isset($thresholds['is_adaptive']) && $thresholds['is_adaptive']
                ],
                'logic_version' => 'v2.0'
            ];

            if ($conf >= $autoThreshold && !$isForbidden) {
                $item['auto_apply'] = true;
                $item['suggested'] = false;
            } else {
                $item['auto_apply'] = false;
                $item['suggested'] = true;

                // Add reason if forbidden
                if ($isForbidden) {
                    $item['reason'] = ($item['reason'] ?? '') . ' (Manuel onay gerekli)';
                }
            }

            // 🆕 Phase 7: Add structured explainability detail
            $item['explainability_detail'] = $this->buildExplainabilityDetail($item);

            $filtered[] = $item;
        }

        return $filtered;
    }

    /**
     * Help resolve category slug from ID for governance overrides
     */
    private function getCategorySlug(?int $categoryId): string
    {
        if (!$categoryId) return 'unknown';

        // Direct mapping for common categories to avoid DB hit every time
        // In production, this can be cached or looked up from IlanKategori model
        $map = [
            1 => 'konut',
            2 => 'isyeri',
            3 => 'arsa',
            4 => 'turistik',
            5 => 'yazlik',
        ];

        return $map[$categoryId] ?? 'other';
    }

    /**
     * 🆕 Phase 7: Build standard explainability detail structure
     */
    private function buildExplainabilityDetail(array $item): array
    {
        $source = $item['source'] ?? 'unknown';

        return [
            'source' => $source,
            'signals' => $this->extractSignals($item),
            'confidence_factors' => $this->calculateConfidenceFactors($item),
            'applied_threshold' => $this->getAppliedThreshold((float)$item['confidence']),
        ];
    }

    private function extractSignals(array $item): array
    {
        $signals = [];

        if (isset($item['source_reference'])) {
            $prefix = ($item['source'] ?? '') === 'image' ? 'filename' : 'keyword';
            $signals[] = "{$prefix}:{$item['source_reference']}";
        }

        return $signals;
    }

    private function calculateConfidenceFactors(array $item): array
    {
        // Heuristic breakdown for Phase 7
        return [
            ['factor' => 'keyword_match', 'weight' => 0.40],
            ['factor' => 'ups_template_match', 'weight' => 0.30],
            ['factor' => 'category_relevance', 'weight' => 0.30],
        ];
    }

    private function getAppliedThreshold(float $confidence): string
    {
        if ($confidence >= 0.80) return 'auto';
        if ($confidence >= 0.50) return 'suggest';
        return 'reject';
    }

    /**
     * Filter features not allowed in current UPS template (Phase 5 Guard)
     */
    private function applyUpsGuard(array $items, int $kategoriId, int $yayinTipiId): array
    {
        /** @var FeatureTemplateResolver $resolver */
        $resolver = app(FeatureTemplateResolver::class);

        // Get all allowed features for this context
        // resolveFeatures returns collection of features with 'slug'
        $allowedFeatures = $resolver->resolveFeatures($kategoriId, $yayinTipiId)->pluck('slug')->toArray();

        $allowedMap = array_flip($allowedFeatures);
        $result = [];

        foreach ($items as $item) {
            if (isset($allowedMap[$item['slug']])) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Extract features with Explainability (Phase 5.2)
     */
    public function extractFromText(string $text): array
    {
        // 1. Normalize Turkish characters
        $normalized = mb_strtolower($text);
        $normalized = str_replace(
            ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'],
            ['i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c'],
            $normalized
        );

        // 2. Keyword Dictionary (Key => Slug)
        $keywordMap = $this->getKeywordMap();
        $results = [];

        // 3. Extraction with Reason matching
        foreach ($keywordMap as $keyword => $slug) {
            // Check for multi-word phrases
            if (str_contains($normalized, $keyword)) {
                // Prevent duplicates, keep highest confidence logic if needed?
                // For now, first match.

                // Lookup dictionary for base confidence if available
                $dictInfo = $this->findInDictionary($slug, $this->getDictionary());
                $baseConfidence = $dictInfo['confidence'] ?? 0.85; // Default high for text match

                // Deduplicate by slug - if already found, maybe boost confidence?
                // Simple implementation: Key by slug
                if (!isset($results[$slug])) {
                    $results[$slug] = [
                        'slug' => $slug,
                        'confidence' => $baseConfidence,
                        'source' => 'text',
                        'reason' => "İlan açıklamasında '$keyword' ifadesi geçtiği için önerildi",
                        'source_reference' => $keyword
                    ];
                }
            }
        }

        return array_values($results);
    }

    private function findInDictionary(string $slug, array $dict): array
    {
        foreach ($dict as $item) {
            if (in_array($slug, $item['slugs'] ?? [], true)) {
                return $item;
            }
        }
        return [];
    }

    private function getKeywordMap(): array
    {
        return [
            // Havuz
            'havuz' => 'ortak-havuz',
            'havuzlu' => 'ortak-havuz',
            'yuzme havuzu' => 'ortak-havuz',

            // Balkon/Teras
            'balkon' => 'balkon',
            'balkonlu' => 'balkon',
            'genis balkon' => 'balkon',
            'teras' => 'teras',
            'terasli' => 'teras',

            // Asansör
            'asansor' => 'asansor',
            'asansorlu' => 'asansor',
            'cift asansor' => 'asansor',

            // Otopark
            'otopark' => 'otopark',
            'kapali otopark' => 'kapali-otopark',
            'acik otopark' => 'acik-otopark',
            'garaj' => 'otopark',

            // Manzara
            'deniz' => 'deniz',
            'deniz manzarasi' => 'deniz-manzarali',
            'deniz manzarali' => 'deniz-manzarali',
            'full deniz' => 'deniz-manzarali',
            'doga manzarasi' => 'doga-manzarali',
            'sehir manzarasi' => 'sehir-manzarali',

            // Isıtma/Soğutma
            'kombi' => 'kombi',
            'yerden isitma' => 'yerden-isitma',
            'merkezi isitma' => 'merkezi-isitma',
            'klima' => 'klima',
            'klimali' => 'klima',

            // Güvenlik
            'guvenlik' => 'guvenlik-sistemi',
            'guvenlikli' => 'guvenlik-sistemi',
            'kamera' => 'kamera-guvenligi',
            '7/24 guvenlik' => 'guvenlik-sistemi',

            // İç Özellikler
            'ankastre' => 'ankastre-mutfak',
            'ankastre set' => 'ankastre-mutfak',
            'ebeveyn' => 'ebeveyn-banyosu',
            'ebeveyn banyosu' => 'ebeveyn-banyosu',
            'jakuzi' => 'jakuzi',
            'sauna' => 'sauna',
            'somine' => 'somine',
            'akilli ev' => 'akilli-ev',

            // Sosyal
            'spor' => 'spor-alani',
            'spor salonu' => 'spor-alani',
            'fitness' => 'spor-alani',
            'cocuk parki' => 'cocuk-oyun-alani',
        ];
    }

    private function getDictionary(): array
    {
        return [
            ['slugs' => ['kaks', 'taks', 'imar', 'imar-durumu', 'parsel', 'ada-parsel', 'imara-acik', 'imar-uygulama', 'elektrik-su', 'bagli-arazi', 'artez-kuyusu'], 'category' => self::CATEGORY_ARSA, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 1.0],
            ['slugs' => ['balkon', 'asansor', 'kombi', 'ankastre-mutfak', 'adsl', 'alarm-hirsiz', 'klima', 'teras', 'camasir-odasi', 'giyinme-odasi', 'gomme-dolap', 'goruntulu-diafon', 'hali-kaplama', 'intercom-sistemi', 'isicam'], 'category' => self::CATEGORY_KONUT, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 1.0],
            ['slugs' => ['kapali-otopark', 'acik-otopark', 'otopark', 'guvenlik-sistemi', 'kamera-guvenligi', 'alarm-yangin'], 'category' => self::CATEGORY_KONUT, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 1.0],
            ['slugs' => ['ortak-havuz', 'ortak-bahce', 'bahce-aydinlatmasi', 'spor-alani', 'cocuk-oyun-alani', 'sosyal-tesis'], 'category' => self::CATEGORY_KONUT, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 1.0],
            ['slugs' => ['okul', 'hastane', 'market', 'avm', 'park', 'toplu-tasima', 'ulasim'], 'category' => self::CATEGORY_KONUT, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 1.0],
            ['slugs' => ['aylik_fiyat', 'haftalik_fiyat', 'gunluk_fiyat', 'sezonluk_fiyat'], 'category' => self::CATEGORY_YAZLIK, 'yayin' => self::YAYIN_KIRALIK, 'confidence' => 1.0],
            ['slugs' => ['bahceli-arazi', 'damla-sulama', 'sera', 'meyve-bahcesi', 'zeytinlik', 'tarimsal-uretim', 'organik-tarim'], 'category' => self::CATEGORY_ARSA, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 1.0],
            ['slugs' => ['deniz', 'bogaz', 'orman', 'gol', 'dag', 'sehir', 'ada', 'nehir', 'vadi'], 'category' => self::CATEGORY_KONUT, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 1.0],
            ['slugs' => ['cadde', 'sokak', 'bulvar', 'bati', 'dogu', 'guney', 'kuzey', 'merkezi', 'sahil'], 'category' => self::CATEGORY_KONUT, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 1.0],
            ['slugs' => ['bebek-dostu-0-2-yas', 'cocuk-dostu-2-12-yas', 'aile-dostu', 'evcil-hayvan-kabul', 'balayi-villasi', 'jakuzi', 'sauna', 'fitness', 'masa-tenisi', 'bilardo'], 'category' => self::CATEGORY_YAZLIK, 'yayin' => self::YAYIN_KIRALIK, 'confidence' => 1.0],
            ['slugs' => ['spor-salonu', 'plaj', 'restoran', 'kafe', 'hamam', 'turk-hamami', 'havaalani'], 'category' => self::CATEGORY_KONUT, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 1.0],
            ['slugs' => ['guneydogu', 'guneybati', 'kuzeydogu', 'kuzeybati', 'yola-cepheli'], 'category' => self::CATEGORY_KONUT, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 1.0],
            ['slugs' => ['doga', 'park-manzara', 'yesil-alan', 'orman-manzara'], 'category' => self::CATEGORY_KONUT, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 1.0],
            ['slugs' => ['luks-tatil-villalari', 'merkezi-konumdaki-evler', 'muhafazakar-villalar', 'deniz-manzarali-villa-ve-evler', 'denize-sifir-villalar'], 'category' => self::CATEGORY_YAZLIK, 'yayin' => self::YAYIN_KIRALIK, 'confidence' => 1.0],
            ['slugs' => ['tapu_tipi', 'krediye_uygun', 'takas', 'ipotekli'], 'category' => self::CATEGORY_KONUT, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 1.0],
            ['slugs' => ['site-ici', 'beyaz-esya', 'amerikan-mutfak', 'amerikan-kapi', 'boyali', 'bulasik-makinesi', 'buzdolabi', 'camasir-kurutma-makinesi', 'camasir-makinesi', 'akilli-ev', 'aluminyum-dograma', 'ahsap-dograma', 'bahce'], 'category' => self::CATEGORY_KONUT, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 0.95],
            ['slugs' => ['cadde-cepheli', 'ofis'], 'category' => self::CATEGORY_ISYERI, 'yayin' => self::YAYIN_KIRALIK, 'confidence' => 0.9],
            ['slugs' => ['celik-kapi', 'dusakabin', 'duvar-kagidi', 'firin', 'fiber-internet'], 'category' => self::CATEGORY_KONUT, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 0.9],
            ['slugs' => ['zeytinli-arazi', 'zeytin-agaci-sayisi', 'zeytinyagi-uretimi', 'organik-sertifikali', 'uzum-cesidi', 'meyve-agaci-sayisi', 'tarima-uygun', 'sulama-sistemi', 'kuyu', 'kose-arsa', 'sifir-arsa', 'yola-cephe-metre'], 'category' => self::CATEGORY_ARSA, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 1.0],
            ['slugs' => ['denize_uzaklik', 'denize-mesafe', 'deniz-manzarali', 'max_misafir', 'evcil-hayvan-kabul-eder'], 'category' => self::CATEGORY_YAZLIK, 'yayin' => self::YAYIN_KIRALIK, 'confidence' => 1.0],
            ['slugs' => ['otel-tesis-izni'], 'category' => self::CATEGORY_ARSA, 'yayin' => self::YAYIN_SATILIK, 'confidence' => 1.0],
        ];
    }
}
