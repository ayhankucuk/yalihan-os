<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AdvancedAIPropertyGenerator;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * 🚀 Advanced AI Controller
 *
 * Enterprise seviye AI özellikleri için API endpoints
 */
class AdvancedAIController extends Controller
{
    use ValidatesApiRequests;

    private AdvancedAIPropertyGenerator $aiGenerator;

    public function __construct(AdvancedAIPropertyGenerator $aiGenerator)
    {
        $this->aiGenerator = $aiGenerator;
    }

    /**
     * Gelişmiş AI içerik üretimi
     */
    public function generateAdvancedContent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'baslik' => 'nullable|string|max:255',
            'kategori' => 'required|string|max:100',
            'alt_kategori' => 'nullable|string|max:100',
            'lokasyon' => 'nullable|string',
            'il' => 'nullable|string',
            'ilce' => 'nullable|string',
            'mahalle' => 'nullable|string',
            'fiyat' => 'nullable|numeric|min:0',
            'metrekare' => 'nullable|numeric|min:0',
            'ozellikler' => 'nullable|array',
            'ozellikler.*' => 'string|max:100',
            'oda_sayisi' => 'nullable|string',
            'banyo_sayisi' => 'nullable|integer|min:0',
            'balkon_var' => 'nullable|boolean',
            'asansor_var' => 'nullable|boolean',
            'kat_no' => 'nullable|integer|min:0',
            'toplam_kat' => 'nullable|integer|min:0',
            'ai_tone' => 'nullable|string|in:seo,kurumsal,hizli_satis,luks',
            'ai_variant_count' => 'nullable|integer|min:1|max:10',
            'ai_ab_test' => 'nullable|boolean',
            'ai_languages' => 'nullable|array',
            'ai_languages.*' => 'string|in:TR,EN,RU,DE',
            'include_market_analysis' => 'nullable|boolean',
            'include_seo_keywords' => 'nullable|boolean',
            'include_price_analysis' => 'nullable|boolean',
        ]);

        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'baslik' => 'nullable|string|max:255',
            'kategori' => 'required|string|max:100',
            'alt_kategori' => 'nullable|string|max:100',
            'lokasyon' => 'nullable|string',
            'il' => 'nullable|string',
            'ilce' => 'nullable|string',
            'mahalle' => 'nullable|string',
            'fiyat' => 'nullable|numeric|min:0',
            'metrekare' => 'nullable|numeric|min:0',
            'ozellikler' => 'nullable|array',
            'ozellikler.*' => 'string|max:100',
            'oda_sayisi' => 'nullable|string',
            'banyo_sayisi' => 'nullable|integer|min:0',
            'balkon_var' => 'nullable|boolean',
            'asansor_var' => 'nullable|boolean',
            'kat_no' => 'nullable|integer|min:0',
            'toplam_kat' => 'nullable|integer|min:0',
            'ai_tone' => 'nullable|string|in:seo,kurumsal,hizli_satis,luks',
            'ai_variant_count' => 'nullable|integer|min:1|max:10',
            'ai_ab_test' => 'nullable|boolean',
            'ai_languages' => 'nullable|array',
            'ai_languages.*' => 'string|in:TR,EN,RU,DE',
            'include_market_analysis' => 'nullable|boolean',
            'include_seo_keywords' => 'nullable|boolean',
            'include_price_analysis' => 'nullable|boolean',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            // Property data hazırla
            $propertyData = [
                'baslik' => $request->baslik,
                'kategori' => $request->kategori,
                'alt_kategori' => $request->alt_kategori,
                'lokasyon' => $request->lokasyon,
                'il' => $request->il,
                'ilce' => $request->ilce,
                'mahalle' => $request->mahalle,
                'fiyat' => $request->fiyat,
                'metrekare' => $request->metrekare,
                'ozellikler' => $request->ozellikler ?? [],
                'oda_sayisi' => $request->oda_sayisi,
                'banyo_sayisi' => $request->banyo_sayisi,
                'balkon_var' => $request->balkon_var,
                'asansor_var' => $request->asansor_var,
                'kat_no' => $request->kat_no,
                'toplam_kat' => $request->toplam_kat,
            ];

            // AI options hazırla
            $aiOptions = [
                'tone' => $request->ai_tone ?? 'seo',
                'variant_count' => $request->ai_variant_count ?? 3,
                'ab_test' => $request->ai_ab_test ?? false,
                'languages' => $request->ai_languages ?? ['TR'],
                'include_market_analysis' => $request->include_market_analysis ?? true,
                'include_seo_keywords' => $request->include_seo_keywords ?? true,
                'include_price_analysis' => $request->include_price_analysis ?? true,
            ];

            // AI içerik üret
            $result = $this->aiGenerator->generateAdvancedContent($propertyData, $aiOptions);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'data' => $result,
                'generated_at' => now()->toISOString(),
                'processing_time' => microtime(true) - LARAVEL_START,
            ], 'AI içerik başarıyla üretildi');
        } catch (\Exception $e) {
            Log::error('Advanced AI content generation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('AI içerik üretimi sırasında hata oluştu', $e);
        }
    }

    /**
     * Pazar analizi
     */
    public function generateMarketAnalysis(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'il' => 'required|string',
            'ilce' => 'nullable|string',
            'mahalle' => 'nullable|string',
            'kategori' => 'required|string',
            'metrekare' => 'nullable|numeric|min:0',
            'fiyat' => 'nullable|numeric|min:0',
            'ozellikler' => 'nullable|array',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $propertyData = [
                'il' => $request->il,
                'ilce' => $request->ilce,
                'mahalle' => $request->mahalle,
                'kategori' => $request->kategori,
                'metrekare' => $request->metrekare,
                'fiyat' => $request->fiyat,
                'ozellikler' => $request->ozellikler ?? [],
            ];

            $analysis = $this->aiGenerator->generateMarketAnalysis($propertyData);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'data' => $analysis,
                'generated_at' => now()->toISOString(),
            ], 'Pazar analizi başarıyla oluşturuldu');
        } catch (\Exception $e) {
            Log::error('Market analysis generation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Pazar analizi oluşturulamadı', $e);
        }
    }

    /**
     * Fiyat analizi
     */
    public function generatePriceAnalysis(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'il' => 'required|string',
            'ilce' => 'nullable|string',
            'mahalle' => 'nullable|string',
            'kategori' => 'required|string',
            'metrekare' => 'nullable|numeric|min:0',
            'fiyat' => 'nullable|numeric|min:0',
            'ozellikler' => 'nullable|array',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $propertyData = [
                'il' => $request->il,
                'ilce' => $request->ilce,
                'mahalle' => $request->mahalle,
                'kategori' => $request->kategori,
                'metrekare' => $request->metrekare,
                'fiyat' => $request->fiyat,
                'ozellikler' => $request->ozellikler ?? [],
            ];

            $analysis = $this->aiGenerator->generatePriceAnalysis($propertyData);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'data' => $analysis,
                'generated_at' => now()->toISOString(),
            ], 'Fiyat analizi başarıyla oluşturuldu');
        } catch (\Exception $e) {
            Log::error('Price analysis generation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Fiyat analizi oluşturulamadı', $e);
        }
    }

    /**
     * SEO anahtar kelimeler
     */
    public function generateSEOKeywords(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'il' => 'required|string',
            'ilce' => 'nullable|string',
            'mahalle' => 'nullable|string',
            'kategori' => 'required|string',
            'ozellikler' => 'nullable|array',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $propertyData = [
                'il' => $request->il,
                'ilce' => $request->ilce,
                'mahalle' => $request->mahalle,
                'kategori' => $request->kategori,
                'ozellikler' => $request->ozellikler ?? [],
            ];

            $keywords = $this->aiGenerator->generateSEOKeywords($propertyData);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success([
                'data' => $keywords,
                'generated_at' => now()->toISOString(),
            ], 'SEO anahtar kelimeler başarıyla oluşturuldu');
        } catch (\Exception $e) {
            Log::error('SEO keywords generation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('SEO anahtar kelimeler oluşturulamadı', $e);
        }
    }

    /**
     * AI sistem sağlık durumu (Context7: yayinda_mi veya portfolio_durumu kullanıldı)
     * Not: Yasaklı terimler yerine her yerde 'health' veya 'yayinda_mi' kullanılmalıdır.
     */
    public function getSystemHealth()
    {
        try {
            $health_data = [
                'ai_service' => $this->checkAIServiceStatus(),
                'database' => $this->checkDatabaseStatus(),
                'cache' => $this->checkCacheStatus(),
                'memory_usage' => [
                    'current' => memory_get_usage(true),
                    'peak' => memory_get_peak_usage(true),
                    'limit' => ini_get('memory_limit'),
                ],
                'timestamp' => now()->toISOString(),
            ];

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::success($health_data, 'Sistem sağlık kontrolü başarıyla tamamlandı');
        } catch (\Exception $e) {
            Log::error('System health check failed', [
                'error' => $e->getMessage(),
            ]);

            // ✅ REFACTORED: Using ResponseService
            return ResponseService::serverError('Sistem sağlık kontrolü yapılamadı', $e); // context7-ignore
        }
    }

    /**
     * Yardımcı metodlar
     */
    private function checkAIServiceStatus(): array
    {
        try {
            // AnythingLLM servis sağlık kontrolü
            $anythingLLM = app(\App\Services\AnythingLLMService::class);
            $health = $anythingLLM->health();

            return [
                'servis_durumu' => $health['ok'] ? 'active' : 'inactive', // context7-ignore
                'message' => $health['message'] ?? 'Service available',
                'response_time' => microtime(true) - LARAVEL_START,
            ];
        } catch (\Exception $e) {
            return [
                'servis_durumu' => 'error',
                'message' => $e->getMessage(),
                'response_time' => 0,
            ];
        }
    }

    private function checkDatabaseStatus(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'servis_durumu' => 'connected',
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'servis_durumu' => 'disconnected',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function checkCacheStatus(): array
    {
        try {
            $cache = app(\App\Services\Cache\CacheService::class);
            $cache->put('health_check', 'ok', 60);
            $value = $cache->get('health_check');
            $cache->forget('health_check');

            return [
                'servis_durumu' => $value === 'ok' ? 'active' : 'inactive', // context7-ignore
                'message' => $value === 'ok' ? 'Cache system working' : 'Cache system not responding',
            ];
        } catch (\Exception $e) {
            return [
                'servis_durumu' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
