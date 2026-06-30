<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\Talep;
use App\Models\Opportunity;
use App\Services\AI\AudioService;
use App\Services\AI\BriefingService;
use App\Services\AI\VoiceSearchService;
use App\Services\AI\YalihanCortex;
use App\Services\AIService;
use App\Services\Logging\LogService;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AIController extends Controller
{
    use ValidatesApiRequests;

    /**
     * YalihanCortex Layer (SSOT AI Brain)
     */
    protected \App\Services\AI\YalihanCortex $cortex;

    public function __construct(\App\Services\AI\YalihanCortex $cortex)
    {
        $this->cortex = $cortex;
    }

    /**
     * Cortex Adapter (SAB P0: Phase 1 Minimal Route)
     * All calls are safely routed here without changing their behavior or prompts.
     */
    private function callCortex(string $action, mixed $payload = null, array $options = [])
    {
        switch ($action) {
            case 'generate_briefing':
                return $this->cortex->generateDailyBriefing();
            case 'analyze_simple':
                return $this->cortex->quickRuleAnalysis($payload, $options);
            case 'calculate_churn_risk':
                return $this->cortex->calculateChurnRisk($payload);
            case 'get_top_churn_risks':
                return $this->cortex->getTopChurnRisks($payload, $options['user_id'] ?? null);
            case 'suggest':
                return $this->cortex->requestLlmSuggestion($payload, $options['type'] ?? 'general');
            case 'generate':
                return $this->cortex->requestLlmGeneration($payload, $options);
            case 'health_check':
                return $this->cortex->checkAiHealth();
            case 'queue_video_render':
                return $this->cortex->requestMarketingVideoRender($payload);
            case 'get_ilan':
                return \App\Models\Ilan::find($payload);
            case 'get_kisi':
                return \App\Models\Kisi::find($payload);
            case 'get_opportunity':
                return \App\Models\Opportunity::find($payload);
            case 'get_providers':
                return $this->cortex->getAvailableProviders();
            case 'switch_provider':
                return $this->cortex->switchAiProvider($payload);
            case 'get_price_metrics':
                // Routed through Cortex proxy
                return $this->cortex->getPriceMetrics($payload);
            case 'create_talep':
                return $this->cortex->prepareTemporaryTalep($payload);
            case 'match_for_sale':
                return $this->cortex->matchForSale($payload);
            case 'submit_feedback':
                return $this->cortex->submitFeedback($payload, $options['data'] ?? [], $options['user_id'] ?? null);
            case 'get_negotiation_strategy':
                return $this->cortex->getNegotiationStrategy($payload);
            case 'voice_to_crm':
                return $this->cortex->createDraftFromText($payload, $options['danisman_id'] ?? null);
            case 'analyze_ai_service':
                return $this->cortex->requestLlmAnalysis($payload, $options);
            case 'text_to_speech':
                return $this->cortex->generateAudioFromText($payload);
            default:
                throw new \Exception("Unknown AI action routed to Cortex: {$action}");
        }
    }

    /**
     * Cortex Briefing: Günlük sabah raporu üret
     *
     * Context7: C7-CORTEX-BRIEFING-2026-01-01
     */
    public function getBriefing(Request $request)
    {
        try {
            $briefing = $this->callCortex('generate_briefing');
            return ResponseService::success($briefing, 'Cortex brifingi başarıyla hazırlandı.');
        }
        catch (\Exception $e) {
           report($e);
            Log::error('AI Briefing Error: ' . $e->getMessage(), ['exception' => $e]);
            return ResponseService::serverError('Brifing hazırlanırken bir hata oluştu.', $e);
        }
    }

    public function analyze(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'action' => 'sometimes|string',  // Made optional
            'data' => 'sometimes|array',     // Made optional
            'context' => 'sometimes|array',
        ]);

        // If validation fails, response already sent
        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $action = $request->input('action', 'talep_analysis');
            $data = $request->input('data', $request->all());
            $context = $request->input('context', []);

            // Simple rule-based analysis (Delegated to Orchestrator)
            $analysis = $this->callCortex('analyze_simple', $data, $context);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'analysis' => $analysis,
                'metadata' => [
                    'cached' => false,
                    'provider' => 'Context7 Rule-Based',
                    'action' => $action,
                ],
            ], 'AI analysis completed successfully');
        }
        catch (\Exception $e) {
           report($e);
            Log::error('AI Analysis Error: ' . $e->getMessage(), ['exception' => $e]);
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('AI analysis failed', $e);
        }
    }

    /**
     * Churn Risk Analizi
     * Context7: YalihanCortex üzerinden yönetilir
     */
    public function getChurnRisk(int $kisiId)
    {
        try {
            $kisi = $this->callCortex('get_kisi', $kisiId);
            if (! $kisi) {
                return ResponseService::notFound('Kişi bulunamadı');
            }

            // ✅ REFACTORED: YalihanCortex üzerinden churn analizi
            $cortexResult = $this->callCortex('calculate_churn_risk', $kisi);

            // Hata durumu kontrolü
            if (isset($cortexResult['success']) && !$cortexResult['success']) {
                return ResponseService::serverError(
                    $cortexResult['error'] ?? 'Churn riski hesaplanamadı',
                    new \Exception($cortexResult['error'] ?? 'Unknown error')
                );
            }

            return ResponseService::success([
                'kisi_id' => $cortexResult['kisi_id'],
                'risk' => [
                    'score' => $cortexResult['risk_score'],
                    'level' => $cortexResult['risk_level'],
                    'breakdown' => $cortexResult['breakdown'],
                    'recommendation' => $cortexResult['recommendation'],
                ],
                'metadata' => array_merge($cortexResult['metadata'] ?? [], [
                    'provider' => 'YalihanCortex',
                    'normalized' => true,
                ]),
            ], 'Churn riski hesaplandı');
        }
        catch (\Exception $e) {
           report($e);
            Log::error('AI Churn Risk Error: ' . $e->getMessage(), ['exception' => $e, 'kisi_id' => $kisiId]);
            return ResponseService::serverError('Churn riski hesaplanamadı', $e);
        }
    }

    /**
     * Top Churn Risks Analizi
     * Context7: YalihanCortex üzerinden yönetilir
     */
    public function getTopChurnRisks(int $limit = 10)
    {
        try {
            $user = auth()->user();

            // ✅ REFACTORED: YalihanCortex üzerinden top churn risks analizi
            $cortexResult = $this->callCortex('get_top_churn_risks', $limit, ['user_id' => $user->id ?? null]);

            // Hata durumu kontrolü
            if (isset($cortexResult['success']) && !$cortexResult['success']) {
                return ResponseService::serverError(
                    $cortexResult['error'] ?? 'Top churn risk listesi oluşturulamadı',
                    new \Exception($cortexResult['error'] ?? 'Unknown error')
                );
            }

            return ResponseService::success([
                'customers' => $cortexResult['customers'] ?? [],
                'count' => $cortexResult['count'] ?? 0,
                'metadata' => array_merge($cortexResult['metadata'] ?? [], [
                    'provider' => 'YalihanCortex',
                    'normalized' => true,
                ]),
            ], 'Top churn risk listesi oluşturuldu');
        }
        catch (\Exception $e) {
           report($e);
            Log::error('AI Top Churn Risks Error: ' . $e->getMessage(), ['exception' => $e]);
            return ResponseService::serverError('Top churn risk listesi oluşturulamadı', $e);
        }
    }

    // Removed simpleAnalysis inline code, now in AIOrchestrator

    public function suggest(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'context' => 'required|array',
            'type' => 'sometimes|string|in:category,feature,content,general', // context7-ignore
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $context = $request->input('context');
            $type = $request->input('type', 'general'); // context7-ignore

            $result = $this->callCortex('suggest', $context, ['type' => $type]);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success($result, 'AI suggestion completed successfully');
        }
        catch (\Exception $e) {
           report($e);
            Log::error('AI Suggestion Error: ' . $e->getMessage(), ['exception' => $e]);
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('AI suggestion failed', $e);
        }
    }

    public function generate(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'prompt' => 'required|string',
            'options' => 'sometimes|array',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $prompt = $request->input('prompt');
            $options = $request->input('options', []);

            $result = $this->callCortex('generate', $prompt, $options);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success($result, 'AI generation completed successfully');
        }
        catch (\Exception $e) {
           report($e);
            Log::error('AI Generation Error: ' . $e->getMessage(), ['exception' => $e]);
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('AI generation failed', $e);
        }
    }

    public function healthCheck()
    {
        try {
            $health = $this->callCortex('health_check');

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success($health, 'AI service health check completed');
        }
        catch (\Exception $e) {
           report($e);
            Log::error('AI Health Check Error: ' . $e->getMessage(), ['exception' => $e]);
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Health check failed', $e);
        }
    }

    /**
     * Pazarlama videosu render sürecini başlatır.
     */
    public function startVideoRender(int $ilanId)
    {
        try {
            $data = $this->callCortex('queue_video_render', $ilanId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Video render işlemi kuyruğa alındı',
            ], 202);
        }
        catch (\Exception $e) {
           report($e);
            Log::error('AI Video Render Start Error: ' . $e->getMessage(), ['exception' => $e, 'ilan_id' => $ilanId]);
            return ResponseService::serverError('Video render işlemi başlatılamadı', $e);
        }
    }

    /**
     * Video statusunu döndürür (polling için).
     */
    public function getVideoStatus(int $ilanId)
    {
        try {
            $ilan = $this->callCortex('get_ilan', $ilanId);
            if (! $ilan) {
                return ResponseService::notFound('İlan bulunamadı');
            }

            return ResponseService::success([
                'ilan_id' => $ilan->id,
                'video_isleme_durumu' => $ilan->video_isleme_durumu ?? 'none',
                'video_last_frame' => (int) ($ilan->video_last_frame ?? 0),
                'video_url' => $ilan->video_url,
            ], 'Video durumu getirildi');
        }
        catch (\Exception $e) {
           report($e);
            Log::error('AI Video Durum Hatası: ' . $e->getMessage(), ['exception' => $e, 'ilan_id' => $ilanId]);
            return ResponseService::serverError('Video durumu alınamadı', $e);
        }
    }

    public function getProviders()
    {
        try {
            $providers = $this->callCortex('get_providers');

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success($providers, 'AI providers retrieved successfully');
        }
        catch (\Exception $e) {
           report($e);
            Log::error('AI Providers Fetch Error: ' . $e->getMessage(), ['exception' => $e]);
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Failed to get providers', $e);
        }
    }

    public function switchProvider(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'provider' => 'required|string',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $provider = $request->input('provider');
            $this->callCortex('switch_provider', $provider);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success(
                ['provider' => $provider],
                'Provider switched successfully'
            );
        }
        catch (\Exception $e) {
           report($e);
            Log::error('AI Provider Switch Error: ' . $e->getMessage(), ['exception' => $e]);
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Failed to switch provider', $e);
        }
    }

    public function getStats()
    {
        try {
            $stats = [
                'total_requests' => \App\Models\AiLog::count(),
                'successful_requests' => \App\Models\AiLog::where('calisma_durumu', 'success')->count(),
                'failed_requests' => \App\Models\AiLog::whereIn('calisma_durumu', ['failed', 'error', 'timeout'])->count(),
                'average_response_time' => \App\Models\AiLog::avg('response_time'),
                'most_used_provider' => \App\Models\AiLog::selectRaw('provider, COUNT(*) as count')
                    ->groupBy('provider')
                    ->orderByDesc('count') // context7-ignore
                    ->first()?->provider ?? 'unknown',
                'requests_today' => \App\Models\AiLog::whereDate('created_at', today())->count(),
                'requests_this_week' => \App\Models\AiLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'requests_this_month' => \App\Models\AiLog::whereMonth('created_at', now()->month)->count(),
            ];

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success($stats, 'AI statistics retrieved successfully');
        }
        catch (\Exception $e) {
           report($e);
            Log::error('AI Stats Fetch Error: ' . $e->getMessage(), ['exception' => $e]);
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Failed to get stats', $e);
        }
    }

    public function getLogs(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'limit' => 'sometimes|integer|min:1|max:100',
            'calisma_durumu' => 'sometimes|string|in:success,error,failed,timeout,cancelled',
            'provider' => 'sometimes|string',
            'action' => 'sometimes|string',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $query = \App\Models\AiLog::query();

            if ($request->has('calisma_durumu')) {
                $query->where('calisma_durumu', $request->input('calisma_durumu'));
            }

            if ($request->has('provider')) {
                $query->where('provider', $request->input('provider'));
            }

            if ($request->has('action')) {
                $query->where('action', $request->input('action'));
            }

            $logs = $query->orderByDesc('created_at') // context7-ignore
                ->limit($request->input('limit', 50))
                ->get();

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success($logs, 'AI logs retrieved successfully');
        }
        catch (\Exception $e) {
           report($e);
            Log::error('AI Logs Fetch Error: ' . $e->getMessage(), ['exception' => $e]);
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Failed to get logs', $e);
        }
    }

    /**
     * AI Başlık Önerisi
     */
    public function suggestTitle(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait with flexible validation
        try {
            $validated = $this->validateRequestFlexible($request, [
                'category' => 'sometimes|string',
                'location' => 'sometimes|string',
                'property_type' => 'sometimes|string',
                'features' => 'sometimes|array',
            ], [
                'category' => ['kategori'],
                'location' => ['lokasyon'],
                'property_type' => ['tip'],
                'features' => ['ozellikler'],
            ]);
        }
        catch (\Illuminate\Validation\ValidationException $e) {
            report($e);
            return ResponseService::validationError($e->errors(), 'Validation failed');
        }

        // Normalize input data
        $category = $validated['category'] ?? 'Gayrimenkul';
        $location = $validated['location'] ??
            ($request->input('il') ? ($request->input('il') . ' ' . ($request->input('ilce') ?? '') . ' ' . ($request->input('mahalle') ?? '')) : '');
        $propertyType = $validated['property_type'] ?? 'Genel';
        $features = $validated['features'] ?? [];

        // If no essential data provided, return helpful error
        if (empty($category) && empty($location) && empty($propertyType)) {
            return ResponseService::error(
                'En az bir alan (kategori, lokasyon veya tip) gereklidir',
                422,
                ['data' => 'Yetersiz veri']
            );
        }

        try {
            // Use normalized data for prompt building
            $normalizedData = [
                'category' => $category,
                'location' => $location,
                'property_type' => $propertyType,
                'features' => $features,
            ];

            $prompt = $this->buildTitlePrompt($normalizedData);
            $result = $this->callCortex('suggest', $normalizedData, ['type' => 'title']);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'suggestions' => $this->parseTitleSuggestions($result),
                'prompt' => $prompt,
            ], 'AI başlık önerileri başarıyla oluşturuldu');
        }
        catch (\Exception $e) {
           report($e);
            Log::error('AI Title Suggestion Error: ' . $e->getMessage(), ['exception' => $e]);
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('AI başlık önerisi alınamadı', $e);
        }
    }

    /**
     * AI Açıklama Üretimi (OLD - Deprecated)
     *
     * @deprecated Use generateDescription() method below
     */
    public function generateDescriptionOld(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'category' => 'required|string',
            'location' => 'required|string',
            'property_type' => 'required|string',
            'features' => 'sometimes|array',
            'price' => 'sometimes|numeric',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $prompt = $this->buildDescriptionPrompt($request->all());
            $result = $this->callCortex('generate', $prompt, ['max_tokens' => 500]);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'description' => $result,
                'prompt' => $prompt,
            ], 'AI açıklama başarıyla oluşturuldu (deprecated method)');
        }
        catch (\Exception $e) {
            report($e);
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('AI açıklama üretilemedi', $e);
        }
    }

    /**
     * AI Fiyat Önerisi (OLD - Deprecated - Use suggestPrice instead)
     *
     * @deprecated Use suggestPrice() method below
     */
    public function suggestPriceOld(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'category' => 'required|string',
            'location' => 'required|string',
            'property_type' => 'required|string',
            'features' => 'sometimes|array',
            'size' => 'sometimes|numeric',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $prompt = $this->buildPricePrompt($request->all());
            $result = $this->callCortex('analyze_ai_service', $request->all(), ['type' => 'price']); // context7-ignore

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'price_suggestion' => $this->parsePriceSuggestion($result),
                'prompt' => $prompt,
            ], 'AI fiyat önerisi başarıyla oluşturuldu (deprecated method)');
        }
        catch (\Exception $e) {
            report($e);
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('AI fiyat önerisi alınamadı', $e);
        }
    }

    /**
     * AI Health Check
     */
    public function health()
    {
        try {
            $health = $this->cortex->checkAiHealth();

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success($health, 'AI service health check completed');
        }
        catch (\Exception $e) {
            report($e);
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('AI health check failed', $e);
        }
    }

    private function buildTitlePrompt($data)
    {
        return "Emlak ilanı için başlık önerileri oluştur:\n" .
            "Kategori: {$data['category']}\n" .
            "Konum: {$data['location']}\n" .
            "Mülk Tipi: {$data['property_type']}\n" .
            'Özellikler: ' . implode(', ', $data['features'] ?? []) . "\n" .
            '3 farklı başlık önerisi ver.';
    }

    private function buildDescriptionPrompt($data)
    {
        return "Emlak ilanı için açıklama yaz:\n" .
            "Kategori: {$data['category']}\n" .
            "Konum: {$data['location']}\n" .
            "Mülk Tipi: {$data['property_type']}\n" .
            'Özellikler: ' . implode(', ', $data['features'] ?? []) . "\n" .
            'Fiyat: ' . ($data['price'] ?? 'Belirtilmemiş') . "\n" .
            'Profesyonel ve çekici bir açıklama yaz.';
    }

    private function buildPricePrompt($data)
    {
        return "Emlak ilanı için fiyat analizi yap:\n" .
            "Kategori: {$data['category']}\n" .
            "Konum: {$data['location']}\n" .
            "Mülk Tipi: {$data['property_type']}\n" .
            'Büyüklük: ' . ($data['size'] ?? 'Belirtilmemiş') . " m²\n" .
            'Özellikler: ' . implode(', ', $data['features'] ?? []) . "\n" .
            'Piyasa analizi yaparak fiyat önerisi ver.';
    }

    private function parseTitleSuggestions($result)
    {
        // AI'dan gelen başlık önerilerini parse et
        $lines = explode("\n", $result);
        $suggestions = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (! empty($line) && ! preg_match('/^\d+\./', $line)) {
                $suggestions[] = $line;
            }
        }

        return array_slice($suggestions, 0, 3);
    }

    private function parsePriceSuggestion($result)
    {
        // AI'dan gelen fiyat önerisini parse et
        preg_match('/\d+[\.,]?\d*/', $result, $matches);

        return $matches[0] ?? 'Belirtilmemiş';
    }

    private function generateCacheKey($action, $data, $context)
    {
        return 'ai_cache_' . md5($action . serialize($data) . serialize($context));
    }

    // ═══════════════════════════════════════════════════════════
    // 🤖 TALEPLER CREATE - AI ASSISTANT ENDPOINTS (2025-11-01)
    // ═══════════════════════════════════════════════════════════

    /**
     * AI Fiyat Önerisi
     * Context7: Lokasyon ve kategori bazlı pazar analizi
     */
    public function suggestPrice(Request $request)
    {
        try {
            $kategoriId = $request->input('kategori_id');
            $ilId = $request->input('il_id');
            $ilceId = $request->input('ilce_id');
            $tip = $request->input('tip');

            // Veritabanından benzer ilanların fiyat istatistiklerini al
            $stats = $this->callCortex('get_price_metrics', [
                'kategori_id' => $kategoriId,
                'il_id' => $ilId,
                'ilce_id' => $ilceId,
                'tip' => $tip,
            ]);

            if (! $stats || $stats->count == 0) {
                // ✅ REFACTORED: Using ResponseService - Varsayılan değerler
                return ResponseService::success([
                    'price' => [
                        'min' => 500000,
                        'avg' => 1000000,
                        'max' => 2000000,
                    ],
                    'metadata' => [
                        'source' => 'default',
                        'count' => 0,
                    ],
                ], 'Benzer ilan bulunamadı, genel pazar verileri gösteriliyor');
            }

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'price' => [
                    'min' => round($stats->min, -3), // Round to thousands
                    'avg' => round($stats->avg, -3),
                    'max' => round($stats->max, -3),
                ],
                'metadata' => [
                    'source' => 'database',
                    'count' => $stats->count,
                    'provider' => 'Context7 Market Analysis',
                ],
            ], 'Fiyat önerisi başarıyla oluşturuldu');
        }
        catch (\Exception $e) {
            report($e);
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Fiyat önerisi alınamadı', $e);
        }
    }

    /**
     * AI İlan Eşleştirme
     * Context7: Talep kriterlerine göre uygun ilanları bul
     *
     * Yeni SmartPropertyMatcherAI servisi kullanılıyor (2025-11-24)
     */
    public function findMatches(Request $request)
    {
        try {
            // Talep ID varsa veritabanından bul, yoksa geçici nesne oluştur
            $talepId = $request->input('talep_id');

            if ($talepId) {
                // Veritabanından Talep'i bul
                $talep = \App\Models\Talep::with(['il', 'ilce', 'mahalle', 'altKategori'])->find($talepId);

                if (! $talep) {
                    return ResponseService::notFound('Talep bulunamadı');
                }
            } else {
                // Request verileriyle geçici Talep nesnesi oluştur (Delegated to Orchestrator)
                $talep = $this->callCortex('create_talep', $request->all());
            }

            // ✅ YalihanCortex ile zenginleştirilmiş eşleştirme
            $cortexResult = $this->callCortex('match_for_sale', $talep);

            // Sonuçları formatla - KÂR ODAKLI ZEKÂ: Action Score, Match Score ve Churn Score ayrı ayrı
            $formattedMatches = collect($cortexResult['matches'] ?? [])->map(function ($match) {
                return [
                    'id' => $match['ilan_id'],
                    'baslik' => $match['baslik'],
                    'title' => $match['baslik'],
                    'price' => $match['fiyat'],
                    'para_birimi' => $match['para_birimi'],
                    // 3 ayrı skor (0-100 arası)
                    'match_score' => round($match['match_score'] ?? 0, 2), // 0-100 arası Match skoru
                    'churn_score' => round($match['churn_score'] ?? 0, 2), // 0-100 arası Churn skoru
                    'action_score' => round($match['action_score'] ?? 0, 2), // 0-100+ arası Action skoru (birleşik)
                    // Normalize edilmiş skorlar (0-1 arası, geriye dönük uyumluluk için)
                    'score' => round(($match['action_score'] ?? 0) / 100, 2), // Action score normalize edilmiş
                    'match_level' => $match['match_level'],
                    'priority' => $match['priority'],
                    'reasons' => $match['reasons'] ?? [],
                    'breakdown' => $match['breakdown'] ?? [],
                ];
            });

            // ✅ REFACTORED: Using ResponseService with YalihanCortex
            return ResponseService::success([
                'matches' => $formattedMatches,
                'count' => $formattedMatches->count(),
                'churn_analysis' => $cortexResult['churn_analysis'] ?? null,
                'recommendations' => $cortexResult['recommendations'] ?? [],
                'metadata' => array_merge($cortexResult['metadata'] ?? [], [
                    'algorithm' => 'YalihanCortex v1.0',
                    'provider' => 'Context7 AI Brain System',
                    'scoring_system' => 'Action Score (Match + Churn * 0.5)',
                    'filter_threshold' => 85, // action_score > 85
                    'max_results' => 5, // İlk 5 ilan
                    'talep_id' => $talepId,
                ]),
            ], 'İlan eşleştirmesi başarıyla tamamlandı');
        }
        catch (\Exception $e) {
            report($e);
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Eşleştirme başarısız', $e);
        }
    }

    // Removed unused formatLocation inline method

    /**
     * AI Akıllı Açıklama Oluşturma
     * Context7: Talep başlığından detaylı açıklama üret
     * With rule-based fallback (no AI service required)
     */
    public function generateDescription(Request $request)
    {
        try {
            $baslik = $request->input('baslik', '');
            $tip = $request->input('tip', '');
            $kategoriId = $request->input('kategori_id');
            $ilId = $request->input('il_id');
            $ilceId = $request->input('ilce_id');

            // Get kategori name
            $kategori = $kategoriId ? \App\Models\IlanKategori::find($kategoriId) : null;
            $kategoriAdi = $kategori->name ?? 'Emlak';

            // Get location names
            $il = $ilId ? \App\Models\Il::find($ilId) : null;
            $ilce = $ilceId ? \App\Models\Ilce::find($ilceId) : null;
            $ilAdi = $il->name ?? '';
            $ilceAdi = $ilce->name ?? '';

            // Try AI service first
            $description = null;
            try {
                $prompt = "Emlak talebi için profesyonel açıklama yaz:

Başlık: {$baslik}
Kategori: {$kategoriAdi}
Tip: {$tip}
Lokasyon: {$ilAdi} {$ilceAdi}

Görev: Müşteri odaklı, profesyonel, 2-3 cümlelik talep açıklaması oluştur.
Açıklama net olmalı ve müşterinin ne aradığını açıkça belirtmeli.

Sadece açıklamayı döndür, başlık veya ek bilgi ekleme.";

                $result = $this->aiService->generate($prompt, [
                    'max_tokens' => 200,
                    'temperature' => 0.7,
                ]);

                $description = $result['data'] ?? null;
            }
            catch (\Exception $aiError) {
               report($aiError);
                Log::error('AI Description Service generation failed: ' . $aiError->getMessage());
                // AI failed, use fallback
                $description = null;
            }

            // Fallback: Rule-based description generation
            if (! $description) {
                $description = $this->cortex->generateDescriptionFallback($baslik, $tip, $kategoriAdi, $ilAdi, $ilceAdi);
            }

            // Clean up the result
            $description = strip_tags($description);
            $description = trim($description);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'description' => $description,
                'metadata' => [
                    'provider' => $description ? 'Context7 Rule-Based' : 'Fallback',
                    'duration' => 0,
                    'tokens' => 0,
                ],
            ], 'AI açıklama başarıyla oluşturuldu');
        }
        catch (\Exception $e) {
           report($e);
            Log::error('Ultimate AI description fallback triggered: ' . $e->getMessage());
            // ✅ REFACTORED: Using ResponseService - Ultimate fallback
            return ResponseService::success([
                'description' => 'Profesyonel bir emlak talebi. Detaylar için lütfen bizi arayın.',
                'metadata' => [
                    'provider' => 'Emergency Fallback',
                    'error' => $e->getMessage(),
                ],
            ], 'Açıklama oluşturuldu (fallback)');
        }
    }

    // Removed generateDescriptionFallback inline method as it was moved to AIOrchestrator

    /**
     * AI Feedback Submission
     * Context7: C7-AI-FEEDBACK-2025-11-25
     * Danışman geri bildirimi: "İşe Yaradı/Yaramadı" + rating + reason
     *
     * ✅ REFACTORED: YalihanCortex üzerinden yönetilir
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitFeedback(Request $request, int $logId)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'rating' => 'required|integer|min:1|max:5',
            'feedback_type' => 'required|string|in:positive,negative,neutral',
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $user = $request->user();

            // ✅ REFACTORED: YalihanCortex üzerinden feedback kaydet
            $cortexResult = $this->callCortex('submit_feedback', $logId, [
                'data' => $request->only(['rating', 'feedback_type', 'reason']),
                'user_id' => $user->id
            ]);

            // Hata statusu kontrolü
            if (! ($cortexResult['success'] ?? false)) {
                $errorCode = $cortexResult['code'] ?? 500;
                if ($errorCode === 403) {
                    return ResponseService::error(
                        $cortexResult['error'] ?? 'Yetkiniz yok',
                        403
                    );
                }
                if ($errorCode === 404 || str_contains($cortexResult['error'] ?? '', 'bulunamadı')) {
                    return ResponseService::notFound($cortexResult['error'] ?? 'AI log kaydı bulunamadı');
                }
                return ResponseService::serverError(
                    $cortexResult['error'] ?? 'Feedback kaydedilemedi',
                    new \Exception($cortexResult['error'] ?? 'Unknown error')
                );
            }

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'log_id' => $cortexResult['log_id'],
                'rating' => $cortexResult['rating'],
                'feedback_type' => $cortexResult['feedback_type'],
                'message' => $cortexResult['message'] ?? 'Geri bildirim başarıyla kaydedildi. AI öğrenme döngüsüne katkı sağladınız!',
                'metadata' => $cortexResult['metadata'] ?? [],
            ], 'Geri bildirim başarıyla kaydedildi. AI öğrenme döngüsüne katkı sağladınız!');
        }
        catch (\Exception $e) {
            report($e);
            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Geri bildirim kaydedilemedi', $e);
        }
    }

    /**
     * Get negotiation strategy for a customer
     *
     * Context7: YalihanCortex üzerinden pazarlık stratejisi analizi
     *
     * @param Request $request
     * @param int $kisiId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNegotiationStrategy(Request $request, int $kisiId)
    {
        try {
            $kisi = $this->callCortex('get_kisi', $kisiId);
            if (! $kisi) {
                 return ResponseService::error('Kişi bulunamadı.', 404);
            }

            $result = $this->callCortex('get_negotiation_strategy', $kisi);

            return ResponseService::success($result, 'Pazarlık stratejisi başarıyla oluşturuldu.');
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            report($e);
            return ResponseService::error('Kişi bulunamadı.', 404);
        }
        catch (\Exception $e) {
            report($e);
            LogService::error(
                'Negotiation strategy API failed',
                [
                    'kisi_id' => $kisiId,
                    'error' => $e->getMessage(),
                ],
                $e,
                LogService::CHANNEL_AI
            );

            return ResponseService::error('Pazarlık stratejisi oluşturulurken bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Sesli komut ile hızlı kayıt oluşturma
     * Context7: C7-VOICE-TO-CRM-2025-11-27
     *
     * Telegram/WhatsApp sesli mesajdan gelen metni parse edip
     * Kisi ve Talep draft kayıtları oluşturur
     *
     * POST /api/v1/admin/ai/voice-to-crm
     *
     * Body:
     * {
     *   "text": "Yeni talep, Ahmet Yılmaz, 10 milyon TL, Bodrum Yalıkavak'ta villa arıyor.",
     *   "danisman_id": 1
     * }
     */
    public function voiceToCrm(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'text' => 'required|string|min:10|max:2000',
            'danisman_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $user = $request->user();
            $danismanId = $validated['danisman_id'] ?? $user->id;
            $text = $validated['text'];

            // ✅ YalihanCortex üzerinden voice-to-crm işlemi
            $cortexResult = $this->callCortex('voice_to_crm', $text, ['danisman_id' => $danismanId]);

            // Hata statusu kontrolü
            if (!($cortexResult['success'] ?? false)) {
                return ResponseService::serverError(
                    $cortexResult['error'] ?? 'Sesli komut kaydı oluşturulamadı',
                    new \Exception($cortexResult['error'] ?? 'Unknown error')
                );
            }

            return ResponseService::success([
                'kisi_id' => $cortexResult['kisi_id'],
                'talep_id' => $cortexResult['talep_id'],
                'kisi' => $cortexResult['kisi'],
                'talep' => $cortexResult['talep'],
                'message' => '✅ Kayıt alındı. Formu daha sonra doldurabilirsiniz.',
                'metadata' => $cortexResult['metadata'] ?? [],
            ], 'Sesli komut başarıyla işlendi');
        }
        catch (\Exception $e) {
           report($e);
            Log::error('VoiceToCrm API failed: ' . $e->getMessage());
            return ResponseService::serverError('Sesli komut işlenirken hata oluştu', $e);
        }
    }

    /**
     * Generate audio for an opportunity (Avcı Modülü)
     * Context7: C7-OPPORTUNITY-AUDIO-2026-01-01
     */
    public function generateOpportunityAudio(int $id)
    {
        try {
            // Önce Opportunity modelinde ara (Cortex Hunter)
            $opportunity = $this->callCortex('get_opportunity', $id);

            if ($opportunity) {
                $text = $opportunity->ikna_metni ?? $opportunity->firsat_nedeni;
            } else {
                // Bulunamazsa ActionScoreService üzerinden Kisi bazlı ara
                $kisi = $this->callCortex('get_kisi', $id);
                if ($kisi) {
                    $actionScoreService = app(\App\Services\Intelligence\ActionScoreService::class);
                    $scoreData = $actionScoreService->calculateActionScore($kisi);
                    $text = $scoreData['recommendation'] ?? null;
                }
            }

            if (empty($text)) {
                return ResponseService::error('Seslendirilecek metin bulunamadı.');
            }

            $audioUrl = $this->callCortex('text_to_speech', $text);

            return ResponseService::success([
                'audio_url' => $audioUrl
            ], 'Fırsat özeti başarıyla seslendirildi.');
        }
        catch (\Exception $e) {
           report($e);
            Log::error('GenerateOpportunityAudio API failed: ' . $e->getMessage());
            return ResponseService::serverError('Ses üretilemedi', $e);
        }
    }
}
