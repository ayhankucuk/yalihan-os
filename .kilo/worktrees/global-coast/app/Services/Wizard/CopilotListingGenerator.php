<?php

namespace App\Services\Wizard;

use App\Models\CopilotActionLog;
use App\Services\AI\DanismanAIService;
use Illuminate\Support\Facades\Log;

class CopilotListingGenerator
{
    public function __construct(
        private readonly EffectiveWizardSchemaResolver $schemaResolver,
        private readonly DanismanAIService $aiService,
        private readonly PricingSuggestionService $pricingService,
    ) {}

    /**
     * Generate copilot actions for the current wizard form state.
     *
     * Accepts the current form payload and returns a list of executable actions
     * with their type, target fields, suggested values, and confidence scores.
     *
     * @param array $formState Current wizard form data (category, location, features, etc.)
     * @param string $mode 'suggest' | 'auto_run' | 'full_generate'
     * @return array Action contract response
     */
    public function generate(array $formState, string $mode = 'suggest'): array
    {
        $startTime = microtime(true);
        $actions = [];

        $categoryId = (int) ($formState['ana_kategori_id'] ?? $formState['kategori_id'] ?? 0);
        $listingTypeId = (int) ($formState['yayin_tipi_id'] ?? 0);

        // Resolve schema for field context
        $schema = [];
        if ($categoryId && $listingTypeId) {
            try {
                $schema = $this->schemaResolver->resolve($categoryId, $listingTypeId);
            } catch (\Throwable $e) {
                Log::warning('CopilotListingGenerator: schema resolve failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // --- Title suggestion ---
        if ($this->shouldSuggestTitle($formState)) {
            $titleAction = $this->generateTitleAction($formState);
            if ($titleAction) {
                $actions[] = $titleAction;
            }
        }

        // --- Description suggestion ---
        if ($this->shouldSuggestDescription($formState)) {
            $descAction = $this->generateDescriptionAction($formState);
            if ($descAction) {
                $actions[] = $descAction;
            }
        }

        // --- Pricing suggestion + display strategy ---
        $pricingSuggestion = null;
        if ($this->shouldSuggestPricing($formState)) {
            $priceAction = $this->generatePricingAction($formState);
            if ($priceAction) {
                $actions[] = $priceAction;
                $pricingSuggestion = [
                    'basarili' => true,
                    'suggested_price' => $priceAction['value'] ?? null,
                    'confidence' => $priceAction['confidence'] ?? 0.0,
                ];
            }
        }

        if ($this->shouldSuggestPricing($formState) || $mode === 'auto_run' || $mode === 'full_generate') {
            $pricingStrategyActions = $this->generatePricingStrategyActions($formState, $mode, $pricingSuggestion);
            $actions = array_merge($actions, $pricingStrategyActions);
        }

        // --- Feature fill suggestions ---
        if ($mode === 'full_generate' || $mode === 'auto_run') {
            $featureActions = $this->generateFeatureActions($formState, $schema);
            $actions = array_merge($actions, $featureActions);
        }

        // Sort by priority (lower number = higher priority)
        usort($actions, fn ($a, $b) => ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50));

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        return [
            'actions' => $actions,
            'mode' => $mode,
            'confidence' => $this->calculateOverallConfidence($actions),
            'meta' => [
                'action_count' => count($actions),
                'duration_ms' => $durationMs,
                'category_id' => $categoryId,
                'listing_type_id' => $listingTypeId,
                'schema_loaded' => !empty($schema),
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Generate a full listing from minimal input.
     *
     * Used when user clicks "Tam İlan Üret" — Copilot fills all empty fields.
     */
    public function generateFullListing(array $formState): array
    {
        return $this->generate($formState, 'full_generate');
    }

    // --- Private helpers ---

    private function shouldSuggestTitle(array $formState): bool
    {
        $baslik = trim($formState['baslik'] ?? '');
        return empty($baslik) || mb_strlen($baslik) < 15;
    }

    private function shouldSuggestDescription(array $formState): bool
    {
        $aciklama = trim($formState['aciklama'] ?? '');
        return empty($aciklama) || mb_strlen($aciklama) < 50;
    }

    private function shouldSuggestPricing(array $formState): bool
    {
        $fiyat = $formState['fiyat'] ?? null;
        return empty($fiyat) || (int) $fiyat === 0;
    }

    private function generateTitleAction(array $formState): ?array
    {
        try {
            $ilanData = $this->buildIlanDataFromFormState($formState);

            $result = $this->aiService->generateListingTitle($ilanData, [
                'tone' => 'seo',
                'provider' => config('ai.default_provider', 'ollama'),
            ]);

            if (!($result['success'] ?? false)) {
                return null;
            }

            $rawText = $result['content'] ?? '';
            $variants = array_filter(array_map('trim', explode("\n", $rawText)));
            $titles = [];
            foreach ($variants as $variant) {
                $clean = preg_replace('/^[\d\.\-\*]+\s*/', '', $variant);
                if (mb_strlen($clean) > 10) {
                    $titles[] = $clean;
                }
            }

            if (empty($titles)) {
                return null;
            }

            return [
                'id' => 'title_' . uniqid(),
                'type' => 'field_autofill', // context7-ignore
                'label' => 'Başlık Önerisi',
                'description' => 'AI tarafından SEO uyumlu başlık oluşturuldu',
                'target' => 'baslik',
                'value' => $titles[0],
                'alternatives' => array_slice($titles, 0, 5),
                'priority' => 10,
                'confidence' => 0.85,
                'requires_confirmation' => true,
                'source' => 'ai_title_generator',
            ];
        } catch (\Throwable $e) {
            Log::warning('CopilotListingGenerator: title generation failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function generateDescriptionAction(array $formState): ?array
    {
        try {
            $ilanData = $this->buildIlanDataFromFormState($formState);

            $result = $this->aiService->generateListingDescription($ilanData, [
                'provider' => config('ai.default_provider', 'ollama'),
            ]);

            if (!($result['success'] ?? false)) {
                return null;
            }

            $content = trim($result['content'] ?? '');
            if (mb_strlen($content) < 30) {
                return null;
            }

            return [
                'id' => 'desc_' . uniqid(),
                'type' => 'field_autofill', // context7-ignore
                'label' => 'Açıklama Önerisi',
                'description' => 'AI tarafından detaylı ilan açıklaması oluşturuldu',
                'target' => 'aciklama',
                'value' => $content,
                'alternatives' => [],
                'priority' => 20,
                'confidence' => 0.80,
                'requires_confirmation' => true,
                'source' => 'ai_description_generator',
            ];
        } catch (\Throwable $e) {
            Log::warning('CopilotListingGenerator: description generation failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function generatePricingAction(array $formState): ?array
    {
        try {
            $suggestion = $this->pricingService->suggest($formState);

            if (!($suggestion['basarili'] ?? false)) {
                return null;
            }

            return [
                'id' => 'price_' . uniqid(),
                'type' => 'pricing_apply', // context7-ignore
                'label' => 'Fiyat Önerisi',
                'description' => $suggestion['reason'] ?? 'Piyasa verilerine dayalı fiyat önerisi',
                'target' => 'fiyat',
                'value' => $suggestion['suggested_price'],
                'alternatives' => [],
                'priority' => 15,
                'confidence' => $suggestion['confidence'] ?? 0.60,
                'requires_confirmation' => true,
                'source' => 'pricing_engine',
                'meta' => [
                    'min_price' => $suggestion['min_price'] ?? null,
                    'max_price' => $suggestion['max_price'] ?? null,
                    'comparable_count' => $suggestion['comparable_count'] ?? 0,
                    'para_birimi' => $suggestion['para_birimi'] ?? 'TRY',
                ],
            ];
        } catch (\Throwable $e) {
            Log::warning('CopilotListingGenerator: pricing suggestion failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Suggest price display strategy and related fields.
     *
     * Policy:
     * - Auto-run never fabricates price if data confidence is weak.
     * - Missing critical data defaults to on_request mode.
     */
    private function generatePricingStrategyActions(array $formState, string $mode, ?array $pricingSuggestion): array
    {
        $actions = [];

        $currentMode = $formState['fiyat_gosterim_modu'] ?? null;
        $fiyat = (float) ($formState['fiyat'] ?? 0);
        $categoryHint = $this->resolveCategoryPricingHint($formState);

        if ($currentMode) {
            return [];
        }

        $recommendedMode = match ($categoryHint) {
            'konut' => 'exact',
            'proje' => 'starting_from',
            'luks' => 'on_request',
            default => 'on_request',
        };

        // Auto-run safety: no price invention when data is missing.
        if ($mode === 'auto_run' && $fiyat <= 0 && empty($pricingSuggestion['basarili'])) {
            $actions[] = [
                'id' => 'price_mode_' . uniqid(),
                'type' => 'pricing_apply', // context7-ignore
                'label' => 'Fiyat stratejisi öner',
                'description' => 'Veri eksik olduğu için güvenli mod: Fiyat için sorunuz',
                'target' => 'fiyat_gosterim_modu',
                'value' => 'on_request',
                'alternatives' => ['exact', 'starting_from', 'hidden'],
                'priority' => 16,
                'confidence' => 0.78,
                'requires_confirmation' => true,
                'source' => 'pricing_strategy_engine',
            ];

            return $actions;
        }

        $confidence = (float) ($pricingSuggestion['confidence'] ?? 0.0);

        if (!empty($pricingSuggestion['basarili']) && $confidence >= 0.70) {
            $recommendedMode = 'starting_from';

            $actions[] = [
                'id' => 'price_mode_' . uniqid(),
                'type' => 'pricing_apply', // context7-ignore
                'label' => 'Fiyat stratejisi öner',
                'description' => 'Piyasa güven skoru yeterli: Başlayan fiyatlar önerildi',
                'target' => 'fiyat_gosterim_modu',
                'value' => 'starting_from',
                'alternatives' => ['exact', 'on_request', 'hidden'],
                'priority' => 16,
                'confidence' => $confidence,
                'requires_confirmation' => true,
                'source' => 'pricing_strategy_engine',
            ];

            if (!empty($pricingSuggestion['suggested_price'])) {
                $actions[] = [
                    'id' => 'starting_price_' . uniqid(),
                    'type' => 'field_autofill', // context7-ignore
                    'label' => 'Başlangıç fiyatı önerisi',
                    'description' => 'Fiyat aralığı için başlangıç değeri',
                    'target' => 'baslangic_fiyati',
                    'value' => (int) $pricingSuggestion['suggested_price'],
                    'alternatives' => [],
                    'priority' => 17,
                    'confidence' => $confidence,
                    'requires_confirmation' => true,
                    'source' => 'pricing_strategy_engine',
                ];
            }

            return $actions;
        }

        // Category-oriented default recommendation
        $actions[] = [
            'id' => 'price_mode_' . uniqid(),
            'type' => 'pricing_apply', // context7-ignore
            'label' => 'Fiyat stratejisi öner',
            'description' => $this->strategyExplanation($recommendedMode),
            'target' => 'fiyat_gosterim_modu',
            'value' => $recommendedMode,
            'alternatives' => ['exact', 'starting_from', 'on_request', 'hidden'],
            'priority' => 16,
            'confidence' => 0.66,
            'requires_confirmation' => true,
            'source' => 'pricing_strategy_engine',
        ];

        return $actions;
    }

    private function resolveCategoryPricingHint(array $formState): string
    {
        $categoryId = (int) ($formState['ana_kategori_id'] ?? $formState['kategori_id'] ?? 0);
        if (!$categoryId) {
            return 'default';
        }

        $kategori = \App\Models\IlanKategori::find($categoryId);
        $name = strtolower((string) ($kategori->ad ?? $kategori->name ?? ''));
        $slug = strtolower((string) ($kategori->slug ?? ''));
        $haystack = $name . ' ' . $slug;

        if (str_contains($haystack, 'luks') || str_contains($haystack, 'lüks') || str_contains($haystack, 'premium')) {
            return 'luks';
        }

        if (str_contains($haystack, 'proje') || str_contains($haystack, 'residence') || str_contains($haystack, 'rezidans')) {
            return 'proje';
        }

        if (str_contains($haystack, 'konut') || str_contains($haystack, 'daire') || str_contains($haystack, 'villa')) {
            return 'konut';
        }

        return 'default';
    }

    private function strategyExplanation(string $mode): string
    {
        return match ($mode) {
            'exact' => 'Net fiyat: hızlı satış ve güçlü SEO',
            'starting_from' => 'Başlayan fiyat: proje/varyasyon ilanları için uygun',
            'on_request' => 'Fiyat için sorunuz: premium ve pazarlık odaklı strateji',
            'hidden' => 'Fiyat gizleme: düşük dönüşüm riski içerebilir',
            default => 'Fiyat stratejisi önerisi',
        };
    }

    private function generateFeatureActions(array $formState, array $schema): array
    {
        $actions = [];
        $features = $formState['features'] ?? [];
        $fields = $schema['fields'] ?? [];

        foreach ($fields as $field) {
            $slug = $field['slug'] ?? '';
            if (empty($slug)) {
                continue;
            }

            // Skip already filled fields
            if (!empty($features[$slug])) {
                continue;
            }

            // Only suggest for fields with defaults or common values
            $defaultValue = $field['default_value'] ?? null;
            if ($defaultValue !== null) {
                $actions[] = [
                    'id' => 'feat_' . $slug . '_' . uniqid(),
                    'type' => 'field_autofill', // context7-ignore
                    'label' => ($field['label'] ?? $slug) . ' Önerisi',
                    'description' => 'Varsayılan değer önerisi',
                    'target' => 'features.' . $slug,
                    'value' => $defaultValue,
                    'alternatives' => [],
                    'priority' => 40,
                    'confidence' => 0.65,
                    'requires_confirmation' => true,
                    'source' => 'schema_default',
                ];
            }
        }

        return $actions;
    }

    private function buildIlanDataFromFormState(array $formState): array
    {
        $data = [];

        // Category name resolution
        $categoryId = $formState['ana_kategori_id'] ?? $formState['kategori_id'] ?? null;
        if ($categoryId) {
            $kategori = \App\Models\IlanKategori::find($categoryId);
            $data['kategori'] = $kategori->ad ?? $kategori->name ?? 'Gayrimenkul';
        }

        // Location resolution
        $ilId = $formState['il_id'] ?? null;
        if ($ilId) {
            $il = \App\Models\Il::find($ilId);
            $data['il'] = $il->il_adi ?? null;
        }

        $ilceId = $formState['ilce_id'] ?? null;
        if ($ilceId) {
            $ilce = \App\Models\Ilce::find($ilceId);
            $data['ilce'] = $ilce->ilce_adi ?? null;
        }

        $mahalleId = $formState['mahalle_id'] ?? null;
        if ($mahalleId) {
            $mahalle = \App\Models\Mahalle::find($mahalleId);
            $data['mahalle'] = $mahalle->mahalle_adi ?? null;
        }

        // Listing type
        $yayinTipiId = $formState['yayin_tipi_id'] ?? null;
        if ($yayinTipiId) {
            $yayinTipi = \App\Models\YayinTipiSablonu::find($yayinTipiId);
            $data['yayin_tipi_adi'] = $yayinTipi->ad ?? $yayinTipi->name ?? 'Satılık';
        }

        // Direct fields
        if (!empty($formState['fiyat'])) {
            $data['fiyat'] = $formState['fiyat'];
        }
        if (!empty($formState['alan_m2'])) {
            $data['alan_m2'] = $formState['alan_m2'];
        }

        // Features summary
        $features = $formState['features'] ?? [];
        if (!empty($features)) {
            $featureParts = [];
            foreach ($features as $slug => $value) {
                if (!empty($value) && $value !== '0') {
                    $featureParts[] = $slug . ': ' . $value;
                }
            }
            if (!empty($featureParts)) {
                $data['features'] = implode(', ', array_slice($featureParts, 0, 15));
            }
        }

        return $data;
    }

    private function calculateOverallConfidence(array $actions): float
    {
        if (empty($actions)) {
            return 0.0;
        }

        $total = array_sum(array_column($actions, 'confidence'));
        return round($total / count($actions), 2);
    }
}
