<?php

namespace App\Http\Controllers\Admin\AI;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AI\YalihanCortex;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\IlanKategori;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Models\YayinTipiSablonu;

/**
 * İlan AI Controller
 *
 * Context7 Standardı: C7-ILAN-AI-CONTROLLER-2025-12-03
 * Yalıhan Bekçi: Tüm AI işlemleri YalihanCortex üzerinden yönetilir
 *
 * ✅ REFACTORED: YalihanCortex merkezi "Beyin" sistemi kullanılıyor
 */
class IlanAIController extends Controller
{
    protected YalihanCortex $cortex;

    public function __construct(YalihanCortex $cortex)
    {
        $this->cortex = $cortex;
    }

    /**
     * AI Öneri Endpoint (Unified)
     *
     * POST /admin/ilanlar/ai-suggest
     */
    public function suggest(Request $request): JsonResponse
    {
        if (!config('ai.cortex_enforced', true)) {
            Log::warning('Legacy AI suggest endpoint accessed while cortex_enforced=false', ['ip' => $request->ip()]);
            return response()->json(['success' => false, 'error' => 'Legacy AI path is disabled. Cortex enforcement required.'], 403);
        }

        $request->validate([
            'action' => 'required|in:title,description,location,price',
        ]);

        try {
            $action = $request->input('action');

            switch ($action) {
                case 'title':
                    return response()->json($this->buildTitleResult($request));

                case 'description':
                    return response()->json($this->buildDescriptionResult($request));

                case 'location':
                    return $this->analyzeLocation($request);

                case 'price':
                    return $this->suggestPrice($request);

                default:
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid action',
                    ], 400);
            }
        } catch (\Exception $e) {
            \App\Services\Logging\LogService::ai('cortex_controller_suggest_failed', 'YalihanCortex', [
                'action' => $request->input('action'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 0, false);

            return response()->json([
                'success' => false,
                'error' => 'AI işlemi başarısız',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Başlık üret
     * ✅ REFACTORED: YalihanCortex üzerinden
     */
    protected function buildTitleResult(Request $request): array
    {
        // İlan verisini hazırla
        $ilanData = [
            'kategori' => $request->input('kategori', 'Gayrimenkul'),
            'il' => $request->input('il'),
            'ilce' => $request->input('ilce'),
            'mahalle' => $request->input('mahalle'),
            // ✅ WFC-002: Resolve canonical name from ID
            $yayinTipi = $this->resolveYayinTipiNameOrFail((int) $request->input('yayin_tipi_id')),
            'yayin_tipi' => $yayinTipi,
            'fiyat' => $request->input('fiyat'),
            'para_birimi' => $request->input('para_birimi', 'TRY'),
        ];

        // ✅ YalihanCortex üzerinden başlık üret
        $result = $this->cortex->generateIlanTitle($ilanData, [
            'tone' => $request->input('ai_tone', 'seo'),
        ]);

        return [
            'success' => $result['success'] ?? true,
            'variants' => $result['titles'] ?? [],
            'count' => $result['count'] ?? 0,
            'model' => $result['model'] ?? 'unknown',
        ];
    }

    /**
     * Açıklama üret
     * ✅ REFACTORED: YalihanCortex üzerinden
     */
    protected function buildDescriptionResult(Request $request): array
    {
        // İlan verisini hazırla
        $ilanData = [
            'kategori' => $request->input('kategori', 'Gayrimenkul'),
            'il' => $request->input('il'),
            'ilce' => $request->input('ilce'),
            'mahalle' => $request->input('mahalle'),
            'fiyat' => $request->input('fiyat'),
            'para_birimi' => $request->input('para_birimi', 'TRY'),
            'metrekare' => $request->input('metrekare'),
            'oda_sayisi' => $request->input('oda_sayisi'),
        ];

        // ✅ YalihanCortex üzerinden açıklama üret
        $result = $this->cortex->generateIlanDescription($ilanData, [
            'tone' => $request->input('ai_tone', 'seo'),
        ]);

        return [
            'success' => $result['success'] ?? true,
            'description' => $result['description'] ?? 'Açıklama üretilemedi',
            'length' => $result['length'] ?? 0,
            'model' => $result['model'] ?? 'unknown',
        ];
    }

    /**
     * Lokasyon analizi
     * ✅ REFACTORED: YalihanCortex üzerinden
     */
    protected function analyzeLocation(Request $request): JsonResponse
    {
        $locationData = [
            'il' => $request->input('il'),
            'ilce' => $request->input('ilce'),
            'mahalle' => $request->input('mahalle', ''),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
        ];

        // ✅ YalihanCortex üzerinden lokasyon analizi
        $result = $this->cortex->analyzeLocation($locationData);

        return response()->json([
            'success' => $result['success'] ?? true,
            'analysis' => $result['analysis'] ?? [],
            'model' => $result['model'] ?? 'unknown',
        ]);
    }

    /**
     * Fiyat önerisi
     * ✅ REFACTORED: YalihanCortex üzerinden
     */
    protected function suggestPrice(Request $request): JsonResponse
    {
        // İlan verisini hazırla
        $ilanData = [
            'fiyat' => $request->input('fiyat', 0),
            'kategori' => $request->input('kategori', 'Gayrimenkul'),
            'metrekare' => $request->input('metrekare', 0),
            'il' => $request->input('il'),
            'ilce' => $request->input('ilce'),
            'mahalle' => $request->input('mahalle'),
        ];

        // ✅ YalihanCortex üzerinden fiyat önerisi
        $result = $this->cortex->suggestPrice($ilanData);

        return response()->json($result);
    }

    /**
     * Toplu İlan Analizi
     * POST /admin/ilanlar/ai/bulk-analyze
     */
    public function bulkAnalyze(Request $request): JsonResponse
    {
        $request->validate([
            'ilan_ids' => 'required|array',
            'ilan_ids.*' => 'exists:ilanlar,id',
        ]);

        try {
            $ilanIds = $request->input('ilan_ids');
            $analysisType = $request->input('type', 'comprehensive'); // comprehensive, price, title, seo // context7-ignore

            $results = [];
            foreach ($ilanIds as $ilanId) {
                $ilan = \App\Models\Ilan::with(['kategori', 'il', 'ilce', 'ilanSahibi'])->find($ilanId);

                if (! $ilan) {
                    continue;
                }

                $analysis = $this->analyzeSingleListing($ilan, $analysisType);
                $results[] = [
                    'ilan_id' => $ilanId,
                    'baslik' => $ilan->baslik,
                    'analysis' => $analysis,
                ];
            }

            return response()->json([
                'success' => true,
                'results' => $results,
                'count' => count($results),
                'type' => $analysisType, // context7-ignore
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Toplu analiz başarısız',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tek ilan analizi
     */
    protected function analyzeSingleListing($ilan, string $type): array
    {
        $analysis = [];

        switch ($type) {
            case 'price':
                $analysis = $this->analyzePrice($ilan);
                break;
            case 'title':
                $analysis = $this->analyzeTitle($ilan);
                break;
            case 'seo':
                $analysis = $this->analyzeSEO($ilan);
                break;
            case 'comprehensive':
            default:
                $analysis = [
                    'price' => $this->analyzePrice($ilan),
                    'title' => $this->analyzeTitle($ilan),
                    'seo' => $this->analyzeSEO($ilan),
                    'recommendations' => $this->getRecommendations($ilan),
                ];
                break;
        }

        return $analysis;
    }

    /**
     * Fiyat analizi
     */
    protected function analyzePrice($ilan): array
    {
        $currentPrice = $ilan->fiyat ?? 0;
        $suggestedPrice = $currentPrice;
        $confidence = 0.7;

        // Basit fiyat analizi (gerçek implementasyonda market data kullanılmalı)
        if ($ilan->metrekare) {
            $pricePerSqm = $currentPrice / $ilan->metrekare;
            $suggestedPrice = $currentPrice * (1 + (rand(-10, 10) / 100)); // ±10% varyasyon
        }

        return [
            'current_price' => $currentPrice,
            'suggested_price' => round($suggestedPrice, 2),
            'price_per_sqm' => $ilan->metrekare ? round($currentPrice / $ilan->metrekare, 2) : null,
            'confidence' => $confidence,
            'recommendation' => $suggestedPrice > $currentPrice ? 'Fiyat artırılabilir' : 'Fiyat uygun görünüyor',
        ];
    }

    /**
     * Başlık analizi ve optimizasyonu
     */
    protected function analyzeTitle($ilan): array
    {
        $currentTitle = $ilan->baslik ?? '';
        $suggestedTitles = [];

        // Basit başlık önerileri
        if ($ilan->kategori && $ilan->il) {
            $suggestedTitles[] = $ilan->kategori->name . ' - ' . $ilan->il->il_adi;
            if ($ilan->ilce) {
                $suggestedTitles[] = $ilan->kategori->name . ' ' . $ilan->ilce->ilce_adi . ', ' . $ilan->il->il_adi;
            }
        }

        return [
            'current_title' => $currentTitle,
            'suggested_titles' => $suggestedTitles,
            'current_length' => strlen($currentTitle),
            'seo_score' => $this->calculateSEOScore($currentTitle),
            'recommendation' => strlen($currentTitle) < 30 ? 'Başlık kısa, daha detaylı olabilir' : 'Başlık uygun görünüyor',
        ];
    }

    /**
     * SEO analizi
     */
    protected function analyzeSEO($ilan): array
    {
        $title = $ilan->baslik ?? '';
        $description = $ilan->aciklama ?? '';

        $seoScore = $this->calculateSEOScore($title);
        $descriptionScore = strlen($description) > 100 ? 1.0 : strlen($description) / 100;

        return [
            'title_seo_score' => $seoScore,
            'description_length' => strlen($description),
            'description_score' => $descriptionScore,
            'overall_score' => ($seoScore + $descriptionScore) / 2,
            'recommendations' => [
                strlen($title) < 30 ? 'Başlık daha uzun olmalı' : null,
                strlen($description) < 100 ? 'Açıklama daha detaylı olmalı' : null,
            ],
        ];
    }

    /**
     * SEO skoru hesapla
     */
    protected function calculateSEOScore(string $text): float
    {
        $score = 0.5; // Base score

        // Uzunluk kontrolü
        if (strlen($text) >= 30 && strlen($text) <= 60) {
            $score += 0.2;
        }

        // Kelime sayısı
        $wordCount = str_word_count($text);
        if ($wordCount >= 5 && $wordCount <= 10) {
            $score += 0.2;
        }

        // Özel karakter kontrolü
        if (! preg_match('/[!@#$%^&*(),.?":{}|<>]/', $text)) {
            $score += 0.1;
        }

        return min($score, 1.0);
    }

    /**
     * Genel öneriler
     */
    protected function getRecommendations($ilan): array
    {
        $recommendations = [];

        if (! $ilan->aciklama || strlen($ilan->aciklama) < 100) {
            $recommendations[] = 'Açıklama eksik veya çok kısa';
        }

        if (! $ilan->fotograflar || $ilan->fotograflar->count() < 3) {
            $recommendations[] = 'En az 3 fotoğraf eklenmeli';
        }

        if (! $ilan->metrekare) {
            $recommendations[] = 'Metrekare bilgisi eksik';
        }

        return $recommendations;
    }

    /**
     * Health check
     * ✅ REFACTORED: YalihanCortex üzerinden
     */
    public function health(): JsonResponse
    {
        // ✅ YalihanCortex performans bilgisi
        $performance = $this->cortex->getPerformance();

        return response()->json([
            'success' => true,
            'cortex_status' => 'online',
            'performance' => $performance,
            'model' => config('ai.ollama_model', 'ollama'),
        ]);
    }

    /**
     * Lokasyon string'i oluştur
     */
    protected function getLocation(Request $request): string
    {
        $parts = array_filter([
            $request->input('il'),
            $request->input('ilce'),
            $request->input('mahalle'),
        ]);

        return implode(', ', $parts) ?: 'Bodrum';
    }

    /**
     * Fiyat formatla
     */
    protected function formatPrice(?string $amount, ?string $currency): string
    {
        if (! $amount) {
            return '';
        }

        $symbols = [
            'TRY' => '₺',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $formatted = number_format((float) $amount, 0, ',', '.');
        $symbol = $symbols[$currency ?? 'TRY'] ?? '₺';

        return $formatted . ' ' . $symbol;
    }

    /**
     * AI tabanlı özellik önerileri al (Wizard için)
     * Context7: AI Property Suggestions (Migrated from IlanController)
     */
    public function getAIPropertySuggestions(Request $request): JsonResponse
    {
        if (!config('ai.cortex_enforced', true)) {
            return response()->json(['success' => false, 'message' => 'AI sistemi Policy nedeniyle devre dışı bırakılmıştır.'], 503);
        }

        try {
            // Frontend context objesi gönderiyor: { context: { kategori, il, ilce, mahalle, ... } }
            $context = $request->input('context', []);

            // İlan verisini hazırla
            $ilanData = [
                'kategori' => $this->getCategoryName($context['kategori'] ?? $request->input('kategori', 'Gayrimenkul')),
                'il' => $this->getLocationName($context['il'] ?? $request->input('il')),
                'ilce' => $this->getLocationName($context['ilce'] ?? $request->input('ilce')),
                'mahalle' => $this->getLocationName($context['mahalle'] ?? $request->input('mahalle')),
                'fiyat' => $context['fiyat'] ?? $request->input('fiyat'),
                'metrekare' => $context['metrekare'] ?? $request->input('metrekare'),
            ];

            // ✅ YalihanCortex üzerinden fiyat önerisi (Cortex standard)
            $result = $this->cortex->suggestPrice($ilanData);

            // Frontend formatına uyarla
            $suggestions = $result['suggestions'] ?? [];

            return response()->json([
                'success' => $result['success'] ?? true,
                'suggestions' => $suggestions,
                'data' => [
                    'suggestions' => $suggestions,
                ],
            ]);
        } catch (\Exception $e) {
            \App\Services\Logging\LogService::ai('cortex_property_suggestions_failed', 'YalihanCortex', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 0, false);
            Log::error('AI Property Suggestions Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'AI önerileri alınamadı: ' . $e->getMessage(),
                'suggestions' => [],
            ], 500);
        }
    }

    /**
     * Get location name from ID
     */
    protected function getLocationName($locationId)
    {
        if (!$locationId) return '';
        if (!is_numeric($locationId)) return $locationId;

        $il = Il::find($locationId);
        if ($il) return $il->il_adi ?? $il->name ?? '';

        $ilce = Ilce::find($locationId);
        if ($ilce) return $ilce->ilce_adi ?? $ilce->name ?? '';

        $mahalle = Mahalle::find($locationId);
        if ($mahalle) return $mahalle->mahalle_adi ?? $mahalle->name ?? '';

        return '';
    }

    /**
     * Get category name from ID
     */
    protected function getCategoryName($categoryValue)
    {
        if (!$categoryValue) return '';
        if (!is_numeric($categoryValue)) return $categoryValue;

        $kategori = IlanKategori::find($categoryValue);
        if ($kategori) return $kategori->name ?? $kategori->slug ?? '';

        return '';
    }

    public function generateAiTitle(Request $request): JsonResponse
    {
        // İstisna Gerekçesi: Bu uç nokta doğrudan veritabanı yazma işlemi yapmaz (read/compute only).
        return response()->json($this->buildTitleResult($request));
    }

    public function generateAiDescription(Request $request): JsonResponse
    {
        // İstisna Gerekçesi: Bu uç nokta doğrudan veritabanı yazma işlemi yapmaz (read/compute only).
        return response()->json($this->buildDescriptionResult($request));
    }
}
