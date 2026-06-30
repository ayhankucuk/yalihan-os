<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Services\AI\SmartFieldGenerationService;
use App\Services\AI\Monitoring\AiTelemetryService; // SSOT: Monitoring namespace
use App\Services\AI\AiLearningSignalService;
use App\Services\AI\AiExperimentService;
use App\Models\AiFeatureUsage;
use App\Services\Logging\LogService;
use App\Services\Response\ResponseService;
use App\Services\Ups\FeatureTemplateResolver;
use App\Services\Template\TemplateService;
use App\Services\AI\VisionAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\Wizard\ListingQualityService;

use App\Services\Wizard\WizardOrchestrator;

/**
 * Wizard Controller
 *
 * Updated Phase 10: ROI Dashboard & A/B Experiments
 */
class WizardController extends Controller
{
    private WizardOrchestrator $hub;

    public function __construct(WizardOrchestrator $hub)
    {
        $this->hub = $hub;
        $this->middleware(function ($request, $next) {
            $this->authorize('create', \App\Models\Ilan::class);
            return $next($request);
        });
    }

    /**
     * 🧠 Phase 18.2: Data Quality Scorer
     * POST /api/v1/wizard/score
     */
    public function calculateQualityScore(Request $request)
    {
        try {
            $validated = $request->validate([
                'category_id' => 'required|integer|exists:ilan_kategorileri,id',
                'yayin_tipi_id' => 'required|integer|exists:yayin_tipi_sablonlari,id',
                'form_data' => 'required|array'
            ]);

            $kategoriId = (int) $validated['category_id'];
            $yayinTipiId = (int) $validated['yayin_tipi_id'];
            $formData = $validated['form_data'];

            // 1. Resolve Template (V2: Master Template)
            // yayin_tipi_id corresponds to YayinTipiSablonu ID directly in V2
            $template = \App\Models\YayinTipiSablonu::where('id', $yayinTipiId)
                ->firstOrFail();

            if (!$template->aktiflik_durumu) {
                // [SAB ENFORCEMENT]: Soft fallback kaldirildi
                throw new \App\Exceptions\PropertyHub\TemplateResolutionException(
                    "Secilen yayin tipi sablonu aktif degil. Skorlama yapilamaz."
                );
            }

            // 2. Resolve AI Template (Optional but recommended for full scoring)
            $upsTemplate = \App\Models\UpsTemplate::forContext($kategoriId, $yayinTipiId)
                ->active() // context7-ignore
                ->first();

            if (!$upsTemplate) {
                 // [SAB ENFORCEMENT]: Soft fallback kaldirildi
                 // Template yoksa quality score 0 donmek veya exception firlatmak deterministiktir.
                 throw new \App\Exceptions\PropertyHub\TemplateResolutionException(
                    "Bu kategori icin aktif bir AI (UPS) Sablonu bulunamadi."
                 );
            }

            // 3. Calculate Score
            $result = $this->hub->qualityService->calculateScore($formData, $upsTemplate);

            return ResponseService::success($result);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            LogService::error('wizard_score_error', ['error' => $e->getMessage()], $e);
            return ResponseService::serverError('Skor hesaplanamadı', $e);
        }
    }

    /**
     * Get features grouped by ui_group for wizard
     */
    public function features(Request $request)
    {
        try {
            $t0 = LogService::startTimer('wizard_features_fetch');

            $validated = $request->validate([
                'category_id' => 'required|integer|exists:ilan_kategorileri,id',
                'yayin_tipi_id' => 'required|integer|exists:yayin_tipi_sablonlari,id',
            ]);

            $kategoriId = (int) $validated['category_id'];
            $yayinTipiId = (int) $validated['yayin_tipi_id'];

            // Phase 4: Use resolver for deep property logic
            $features = $this->hub->resolver->resolveFeatures($kategoriId, $yayinTipiId);

            // Grouping logic (Step 2 - Photos)
            $grouped = [];
            foreach ($features as $feature) {
                $group = $feature['ui_group'] ?? 'Genel Özellikler';
                if (!isset($grouped[$group])) {
                    $grouped[$group] = [];
                }
                $grouped[$group][] = $feature;
            }

            LogService::stopTimer($t0, [
                'count' => count($features),
                'kategori' => $kategoriId,
            ]);

            return ResponseService::success($grouped);

        } catch (\Exception $e) {
            LogService::error('wizard_features_error', ['error' => $e->getMessage()], $e);
            return ResponseService::serverError('Özellik listesi alınamadı', $e);
        }
    }

    /**
     * 👁️ Visual AI Analysis (Phase 8)
     */
    public function analyzeImages(Request $request)
    {
        try {
            $t0 = LogService::startTimer('wizard_visual_analysis');

            $validated = $request->validate([
                'images' => 'required|array',
                'images.*' => 'string',
                'category_id' => 'nullable|integer|exists:ilan_kategorileri,id',
                'yayin_tipi_id' => 'nullable|integer|exists:yayin_tipi_sablonlari,id',
                'category_slug' => 'nullable|string',
            ]);

            $images = $validated['images'];
            $kategoriId = isset($validated['category_id']) ? (int) $validated['category_id'] : null;
            $yayinTipiId = isset($validated['yayin_tipi_id']) ? (int) $validated['yayin_tipi_id'] : null;
            $categorySlug = $validated['category_slug'] ?? 'all';

            // Phase 10: Check for active A/B experiments
            $variant = $this->hub->experimentService->getActiveVariation($categorySlug, auth()->id());

            // 👁️ Phase 8: Real Vision AI Integration
            // Pass experiment config if exists
            $visionResult = $this->hub->visionService->analyzeImages($images, $kategoriId, $yayinTipiId, $variant['config'] ?? []);

            if (!$visionResult->success) {
                return ResponseService::serverError($visionResult->errorMessage ?? 'Görsel analiz başarısız oldu');
            }

            $suggestions = $visionResult->suggestions;

            // 📊 Phase 7/10/11: Log Telemetry with Experiment context and Vision Metadata
            $this->logAutoAppliedSuggestions($suggestions, $kategoriId, $yayinTipiId, 'image', $request, $variant, $visionResult->metadata);

            LogService::info('wizard_visual_analysis_success', [
                'image_count' => count($images),
                'suggestions_found' => count($suggestions),
                'experiment' => $variant['deney_id'] ?? null,
                'duration_ms' => (int) LogService::stopTimer($t0),
            ]);

            return ResponseService::success([
                'suggestions' => $suggestions,
                'metadata' => array_merge($visionResult->metadata, ['deney_id' => $variant['deney_id'] ?? null]),
                'message' => 'Görsel analiz tamamlandı'
            ]);

        } catch (\Exception $e) {
            LogService::error('wizard_visual_analysis_error', ['error' => $e->getMessage()], $e);
            return ResponseService::serverError('Görsel analiz sırasında hata oluştu', $e);
        }
    }

    /**
     * 📊 Phase 7/10: Log AI feature action (telemetry)
     * POST /api/v1/wizard/telemetry/feature-action
     */
    public function logFeatureAction(Request $request)
    {
        try {
            $validated = $request->validate([
                'kategori_id' => 'required|integer',
                'yayin_tipi_id' => 'required|integer',
                'feature_slug' => 'required|string|max:120',
                'confidence' => 'required|numeric|min:0|max:1',
                'source_tipi' => 'required|in:text,image,mixed',
                'aksiyon' => 'required|in:auto_applied,user_applied,dismissed,skipped_ups_guard,suggested,api_error',
                'neden' => 'nullable|string',
                'neden_detay' => 'nullable|array',
                'explainability_v2' => 'nullable|array',
                'istek_id' => 'nullable|string|max:64',
                'ilan_id' => 'nullable|integer',

                // Phase 10 & 11: Efficiency & Experimentation & Cost Guard
                'etkilesim_suresi_ms' => 'nullable|integer',
                'deney_id' => 'nullable|integer',
                'deney_varyasyon_anahtari' => 'nullable|string',
                'maliyet_usd' => 'nullable|numeric',
                'latency_ms' => 'nullable|integer',
                'cache_hit' => 'nullable|boolean',
                'provider' => 'nullable|string|max:32'
            ]);

            $this->hub->telemetryService->logFeatureUsage($validated);

            return ResponseService::success(['success' => true], 'Telemetry loglandı');
        } catch (\Exception $e) {
            Log::error('AI Telemetry endpoint failure', ['error' => $e->getMessage()]);
            return ResponseService::serverError('Telemetry kaydedilemedi');
        }
    }

    /**
     * Helper to log auto-applied suggestions for telemetry
     */
    private function logAutoAppliedSuggestions(
        array $suggestions,
        ?int $categoryId,
        ?int $yayinTipiId,
        string $source,
        Request $request,
        ?array $variant = null,
        array $metadata = []
    ): void {
        if (!$categoryId || !$yayinTipiId) return;

        $istekId = $request->header('X-Request-ID') ?? uniqid('ai_req_');

        foreach ($suggestions as $suggestion) {
            if ($suggestion['auto_apply'] ?? false) {
                $this->hub->telemetryService->logFeatureUsage([
                    'kategori_id' => $categoryId,
                    'yayin_tipi_id' => $yayinTipiId,
                    'feature_slug' => $suggestion['slug'],
                    'confidence' => $suggestion['confidence'],
                    'source_tipi' => $source,
                    'aksiyon' => 'auto_applied',
                    'neden' => $suggestion['reason'] ?? null,
                    'neden_detay' => $suggestion['explainability_detail'] ?? null,
                    'explainability_v2' => $suggestion['explainability_v2'] ?? null,
                    'istek_id' => $istekId,

                    // Phase 10 context
                    'deney_id' => $variant['deney_id'] ?? null,
                    'deney_varyasyon_anahtari' => $variant['varyasyon_anahtari'] ?? null,

                    // Phase 11 & Cost Guard context
                    'provider' => $metadata['provider'] ?? null,
                    'latency_ms' => $metadata['latency_ms'] ?? null,
                    'maliyet_usd' => $metadata['cost_estimate'] ?? null,
                    'cache_hit' => $metadata['cache_hit'] ?? false,
                ]);
            }
        }
    }
    /**
     * AI Suggestion Engine
     * POST /api/v1/wizard/suggest
     */
    public function suggest(Request $request)
    {
        try {
            $validated = $request->validate([
                'description' => 'required|string',
                'category_id' => 'nullable|integer|exists:ilan_kategorileri,id',
                'yayin_tipi_id' => 'nullable|integer|exists:yayin_tipi_sablonlari,id',
            ]);

            $description = $validated['description'];
            $kategoriId = isset($validated['category_id']) ? (int) $validated['category_id'] : null;
            $yayinTipiId = isset($validated['yayin_tipi_id']) ? (int) $validated['yayin_tipi_id'] : null;

            $extracted = $this->hub->aiService->extractFromText($description);

            $suggestions = $this->hub->aiService->generateSmartRecommendations(
                $extracted,
                $kategoriId,
                $yayinTipiId
            );

            $threshold = config('ai-governance.adaptive_thresholds.suggest', 0.60);

            return ResponseService::success([
                'suggestions' => $suggestions,
                'ai_confidence_threshold' => $threshold,
            ]);

        } catch (\Exception $e) {
            LogService::error('wizard_suggest_error', ['error' => $e->getMessage()], $e);
            return ResponseService::serverError('Öneri alınamadı', $e);
        }
    }

        public function priceToText(Request $request)
        {
            $validated = $request->validate([
                'fiyat' => 'required|numeric|min:0',
                'para_birimi' => 'nullable|string|max:10',
            ]);

            $fiyat = (float) $validated['fiyat'];
            $paraBirimi = $validated['para_birimi'] ?? 'TRY';

            return ResponseService::success([
                'fiyat_metin' => number_format($fiyat, 0, ',', '.') . ' ' . $paraBirimi,
            ], 'Fiyat metni hazır');
        }

        public function validateStep2(Request $request)
        {
            $rules = [
                'ana_kategori_id' => 'required|integer|exists:ilan_kategorileri,id',
                'yayin_tipi_id' => 'required|integer|exists:ilan_kategorileri,id',
            ];

            // Conditionally require baslik and fiyat if step-2 has them
            if ($request->has('baslik')) {
                $rules['baslik'] = 'required|string|min:10|max:255';
            }
            if ($request->has('fiyat')) {
                $rules['fiyat'] = 'required|numeric|min:0';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return ResponseService::success([
                    'gecerli' => false,
                    'hatalar' => $validator->errors()->toArray(),
                ], 'Doğrulama hatası');
            }

            return ResponseService::success([
                'gecerli' => true,
                'hatalar' => [],
            ], '2. adım doğrulandı');
        }

    /**
     * 🧠 Phase 13: Feature Feedback Loop
     * POST /api/v1/wizard/feature-feedback
     */
    public function featureFeedback(Request $request)
    {
        try {
            $validated = $request->validate([
                'ai_feature_usage_id' => 'required|exists:ai_feature_usages,id',
                'slug' => 'required|string',
                'karar' => 'required|in:applied,dismissed,auto_reverted',
            ]);

            $usage = AiFeatureUsage::findOrFail($validated['ai_feature_usage_id']);

            $allowedSlugs = $this->hub->visionService->getAllowedSlugs($usage->kategori_id, $usage->yayin_tipi_id);

            if (!in_array($validated['slug'], $allowedSlugs)) {
                 return ResponseService::validationError(['slug' => ['Feature not in UPS template']], 'Bu özellik bu kategori için geçerli değil (UPS Guard)');
            }

            $signal = $this->hub->learningService->recordSignal($usage, $validated['karar']);

            if (!$signal) {
                return ResponseService::serverError('Öğrenme sinyali kaydedilemedi');
            }

            $usage->update(['aksiyon' => $validated['karar']]);

            return ResponseService::success([
                'id' => $signal->id,
                'skor' => $signal->skor
            ], 'Geri bildirim alındı');

        } catch (\Exception $e) {
             LogService::error('feature_feedback_error', ['error' => $e->getMessage()], $e);
             return ResponseService::serverError('Geri bildirim işlenirken hata oluştu', $e);
        }
    }
    /**
     * 📏 Phase 9: Frontend Validation Rules (Context7)
     * GET /api/v1/wizard/validation-rules
     */
    public function validationRules(Request $request)
    {
        // SSOT: Accept multiple param formats for compatibility
        $kategoriId = $request->input('alt_kategori_id')
                    ?? $request->input('category_id')
                    ?? $request->input('kategori_id');
        $yayinTipiId = $request->input('yayin_tipi_id');

        if (!$kategoriId) {
            return ResponseService::error('Kategori ID zorunludur', 400);
        }

        try {
            $templateData = $this->hub->templateService->autoSelectTemplate(
                (int)$kategoriId,
                $yayinTipiId ? (int)$yayinTipiId : null,
                $request->all()
            );

            return ResponseService::success([
                'rules' => $templateData['validation']['rules'] ?? [],
                'messages' => $templateData['validation']['messages'] ?? [],
                'template_id' => $templateData['template_id'] ?? null
            ]);
        } catch (\Exception $e) {
            return ResponseService::success([
                'rules' => [],
                'messages' => []
            ]);
        }
    }

    /**
     * 🎨 Phase 4: Template Auto Select
     * GET /api/v1/wizard/template-auto-select
     */
    public function templateAutoSelect(Request $request)
    {
        $kategoriId = $request->input('alt_kategori_id') ?? $request->input('kategori_id');
        $yayinTipiId = $request->input('yayin_tipi_id');

        if (!$kategoriId) {
            return ResponseService::error('Kategori ID zorunludur');
        }

        try {
            $result = $this->hub->templateService->autoSelectTemplate(
                (int)$kategoriId,
                $yayinTipiId ? (int)$yayinTipiId : null,
                $request->all()
            );

            return ResponseService::success($result);

        } catch (\App\Exceptions\PropertyHub\TemplateResolutionException $e) {
            // [SAB ENFORCEMENT]: Deterministic error — sablon bulunamadi
            LogService::error('template_auto_select_failed', [
                'kategori_id' => $kategoriId,
                'yayin_tipi_id' => $yayinTipiId,
                'error' => $e->getMessage()
            ]);

            return ResponseService::error($e->getMessage(), 404);

        } catch (\Exception $e) {
            LogService::error('template_auto_select_failed', [
                'kategori_id' => $kategoriId,
                'yayin_tipi_id' => $yayinTipiId,
                'error' => $e->getMessage()
            ]);

            return ResponseService::serverError('Sablon cozumlemesi sirasinda beklenmeyen hata olustu.', $e);
        }
    }
}
