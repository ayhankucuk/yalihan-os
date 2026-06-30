<?php

namespace App\Http\Controllers\Api;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Helpers\ConfigOptionHelper;
use App\Http\Controllers\Controller;
use App\Services\CortexKnowledgeService;
use App\Services\Response\ResponseService;
use App\Services\Integrations\TKGMService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * İlan AI Controller
 *
 * Context7 Standard: C7-ILAN-AI-API-2025-11-30
 *
 * AI-powered endpoints for listing management
 */
class IlanAIController extends Controller
{
    use ValidatesApiRequests;

    protected TKGMService $tkgmService;

    protected CortexKnowledgeService $cortexKnowledgeService;

    protected \App\Services\Finance\PricingService $pricingService;

    public function __construct(
        TKGMService $tkgmService,
        CortexKnowledgeService $cortexKnowledgeService,
        \App\Services\Finance\PricingService $pricingService
    ) {
        $this->tkgmService = $tkgmService;
        $this->cortexKnowledgeService = $cortexKnowledgeService;
        $this->pricingService = $pricingService;
    }

    /**
     * TKGM'den parsel bilgilerini çek
     *
     * POST /api/ai/fetch-tkgm
     *
     * Input: il_id, ilce_id, mahalle_id, ada_no, parsel_no
     * Response: { "alan_m2": 1500.50, "lat": 38.4, "lng": 27.1, ... }
     */
    public function fetchTkgm(Request $request): JsonResponse
    {
        $validated = $this->validateRequestWithResponse($request, [
            'il_id' => 'required|exists:iller,id',
            'ilce_id' => 'required|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'ada_no' => 'required|string|max:20',
            'parsel_no' => 'required|string|max:20',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            // İl, ilçe, mahalle isimlerini çek
            $il = \App\Models\Il::findOrFail($validated['il_id']);
            $ilce = \App\Models\Ilce::findOrFail($validated['ilce_id']);
            $mahalle = $validated['mahalle_id']
                ? \App\Models\Mahalle::findOrFail($validated['mahalle_id'])
                : null;

            // TKGM servisinden parsel bilgilerini çek
            $result = $this->tkgmService->parselSorgula(
                $validated['ada_no'],
                $validated['parsel_no'],
                $il->il_adi,
                $ilce->ilce_adi,
                $mahalle?->mahalle_adi
            );

            if (!isset($result['success']) || !$result['success']) {
                return ResponseService::error(
                    $result['message'] ?? 'TKGM sorgulama başarısız',
                    400
                );
            }

            // Response formatını standardize et
            $parselBilgileri = $result['parsel_bilgileri'] ?? $result;

            return ResponseService::success([
                'alan_m2' => $parselBilgileri['alan_m2'] ?? $parselBilgileri['alan'] ?? null,
                'lat' => $parselBilgileri['lat'] ?? $parselBilgileri['latitude'] ?? null,
                'lng' => $parselBilgileri['lng'] ?? $parselBilgileri['longitude'] ?? null,
                'imar_durumu' => $parselBilgileri['imar_durumu'] ?? $parselBilgileri['zoning_status'] ?? null,
                'kaks' => $parselBilgileri['kaks'] ?? null,
                'taks' => $parselBilgileri['taks'] ?? null,
                'gabari' => $parselBilgileri['gabari'] ?? null,
                'from_cache' => $result['from_cache'] ?? false,
                'raw_data' => $parselBilgileri, // Tam veri (debugging için)
            ], 'TKGM sorgulama başarılı');
        } catch (\Exception $e) {
            return ResponseService::serverError(
                'TKGM sorgulama sırasında bir hata oluştu: ' . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * m² Fiyatı hesapla
     *
     * POST /api/ai/calculate-m2-price
     *
     * Input: satis_fiyati, alan_m2
     * Logic: satis_fiyati / alan_m2
     * Response: { "m2_fiyati": 3500 }
     */
    public function calculateM2Price(Request $request): JsonResponse
    {
        $validated = $this->validateRequestWithResponse($request, [
            'satis_fiyati' => 'required|numeric|min:0',
            'alan_m2' => 'required|numeric|min:0.01', // En az 0.01 m² olmalı
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            $satisFiyati = (float) $validated['satis_fiyati'];
            $alanM2 = (float) $validated['alan_m2'];

            if ($alanM2 <= 0) {
                return ResponseService::error(
                    'Alan metrekare değeri 0\'dan büyük olmalıdır',
                    400
                );
            }

            // m² fiyatı hesapla: satis_fiyati / alan_m2
            $m2Fiyati = round($satisFiyati / $alanM2, 2);

            return ResponseService::success([
                'm2_fiyati' => $m2Fiyati,
                'satis_fiyati' => $satisFiyati,
                'alan_m2' => $alanM2,
                'formula' => "{$satisFiyati} / {$alanM2} = {$m2Fiyati}",
            ], 'm² fiyatı başarıyla hesaplandı');
        } catch (\Exception $e) {
            return ResponseService::serverError(
                'm² fiyatı hesaplanırken bir hata oluştu: ' . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * İmar Plan ve İnşaat Hakları Analizi
     *
     * POST /api/ai/analyze-construction
     *
     * Input: ada_no, parsel_no, alan_m2, ilce, mahalle (opsiyonel)
     * Response: { "kaks": 2.0, "taks": 0.6, "gabari": 12.5, ... }
     *
     * Context7: CortexKnowledgeService ile AnythingLLM RAG entegrasyonu
     */
    public function analyzeConstruction(Request $request): JsonResponse
    {
        $validated = $this->validateRequestWithResponse($request, [
            'ada_no' => 'required|string|max:20',
            'parsel_no' => 'required|string|max:20',
            'alan_m2' => 'required|numeric|min:0.01',
            'ilce' => 'required|string|max:100',
            'mahalle' => 'nullable|string|max:100',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            // CortexKnowledgeService'e gönderilecek veri formatı
            $data = [
                'ilce' => $validated['ilce'],
                'mahalle' => $validated['mahalle'] ?? null,
                'ada' => $validated['ada_no'],
                'parsel' => $validated['parsel_no'],
                'm2' => (float) $validated['alan_m2'],
            ];

            // CortexKnowledgeService ile AnythingLLM'e sorgu gönder
            $result = $this->cortexKnowledgeService->queryConstructionRights($data);

            if (! $result['success']) {
                return ResponseService::error(
                    $result['message'] ?? 'İmar plan analizi başarısız',
                    400
                );
            }

            // Başarılı response
            return ResponseService::success(
                $result['data'],
                'İmar plan analizi tamamlandı',
                200,
                [
                    'source' => $result['source'] ?? 'AnythingLLM - İmar Plan Notları',
                ]
            );
        } catch (\Exception $e) {
            return ResponseService::serverError(
                'İmar plan analizi sırasında bir hata oluştu: ' . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Yazlık Sezonluk Fiyatlandırma Hesaplama
     *
     * POST /api/ai/calculate-seasonal-price
     *
     * Input: gunluk_fiyat (required|numeric)
     * Response: { "haftalik": 66500, "aylik": 255000, "kis_sezonu_gunluk": 5000, ... }
     *
     * Context7: C7-YAZLIK-PRICING-AUTOMATION-2025-11-30
     */
    public function calculateSeasonalPrice(Request $request): JsonResponse
    {
        $validated = $this->validateRequestWithResponse($request, [
            'gunluk_fiyat' => 'required|numeric|min:0.01',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            $gunlukFiyat = (float) $validated['gunluk_fiyat'];

            // ✅ MIGRATED: Use PricingService for calculations
            $haftalikFiyat = $this->pricingService->calculateRentalPrice($gunlukFiyat, 'weekly');
            $aylikFiyat = $this->pricingService->calculateRentalPrice($gunlukFiyat, 'monthly');

            // ✅ MIGRATED: ConfigOptionHelper now uses Feature system (no config fallback)
            // Context7: C7-CONFIG-MIGRATION-2025-12-23
            $pricingRules = ConfigOptionHelper::get('pricing_rules', null, null, []);
            $seasonalMultipliers = $pricingRules['seasonal_multipliers'] ?? [];

            // Varsayılan değerler (config yoksa)
            $yazMultiplier = $seasonalMultipliers['yaz'] ?? 1.00;      // %100
            $araSezonMultiplier = $seasonalMultipliers['ara_sezon'] ?? 0.70; // %70
            $kisMultiplier = $seasonalMultipliers['kis'] ?? 0.50;      // %50

            // Sezonluk günlük fiyatlar
            $yazSezonuGunluk = $gunlukFiyat * $yazMultiplier;
            $araSezonGunluk = $gunlukFiyat * $araSezonMultiplier;
            $kisSezonuGunluk = $gunlukFiyat * $kisMultiplier;

            // Sezonluk haftalık fiyatlar
            $yazSezonuHaftalik = $this->pricingService->calculateRentalPrice($yazSezonuGunluk, 'weekly');
            $araSezonHaftalik = $this->pricingService->calculateRentalPrice($araSezonGunluk, 'weekly');
            $kisSezonuHaftalik = $this->pricingService->calculateRentalPrice($kisSezonuGunluk, 'weekly');

            // Sezonluk aylık fiyatlar
            $yazSezonuAylik = $this->pricingService->calculateRentalPrice($yazSezonuGunluk, 'monthly');
            $araSezonAylik = $this->pricingService->calculateRentalPrice($araSezonGunluk, 'monthly');
            $kisSezonuAylik = $this->pricingService->calculateRentalPrice($kisSezonuGunluk, 'monthly');

            return ResponseService::success([
                'gunluk_fiyat' => round($gunlukFiyat, 2),
                'haftalik_fiyat' => round($haftalikFiyat, 2),
                'aylik_fiyat' => round($aylikFiyat, 2),
                'sezonluk_fiyatlar' => [
                    'yaz' => [
                        'gunluk' => round($yazSezonuGunluk, 2),
                        'haftalik' => round($yazSezonuHaftalik, 2),
                        'aylik' => round($yazSezonuAylik, 2),
                    ],
                    'ara_sezon' => [
                        'gunluk' => round($araSezonGunluk, 2),
                        'haftalik' => round($araSezonHaftalik, 2),
                        'aylik' => round($araSezonAylik, 2),
                    ],
                    'kis' => [
                        'gunluk' => round($kisSezonuGunluk, 2),
                        'haftalik' => round($kisSezonuHaftalik, 2),
                        'aylik' => round($kisSezonuAylik, 2),
                    ],
                ],
                // Removes explicit formula response as logic is now encapsulated
            ], 'Sezonluk fiyatlandırma hesaplaması tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::serverError(
                'Sezonluk fiyatlandırma hesaplanırken bir hata oluştu: ' . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Konut Metrikleri Hesaplama
     *
     * POST /api/ai/calculate-konut-metrics
     *
     * Input: satis_fiyati, brut_m2
     * Response: { "m2_birim_fiyat": 25000, "formatted": "25.000 TL/m²" }
     *
     * Context7: C7-KONUT-METRICS-2025-11-30
     */
    public function calculateKonutMetrics(Request $request): JsonResponse
    {
        $validated = $this->validateRequestWithResponse($request, [
            'satis_fiyati' => 'required|numeric|min:0.01',
            'brut_m2' => 'required|numeric|min:10',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            $satisFiyati = (float) $validated['satis_fiyati'];
            $brutM2 = (float) $validated['brut_m2'];

            if ($brutM2 <= 0) {
                return ResponseService::error(
                    'Brüt metrekare değeri 0\'dan büyük olmalıdır',
                    400
                );
            }

            // m² birim fiyatı hesapla: satis_fiyati / brut_m2
            $m2BirimFiyat = round($satisFiyati / $brutM2, 2);

            // Formatlanmış değer (Türkçe format)
            $formatted = number_format($m2BirimFiyat, 0, ',', '.') . ' TL/m²';

            // Piyasa analizi (basit karşılaştırma)
            $piyasaOrtalamasi = 35000; // TL/m² (örnek değer, gerçekte veritabanından çekilebilir)
            $durum = $m2BirimFiyat > $piyasaOrtalamasi ? 'üstünde' : ($m2BirimFiyat < $piyasaOrtalamasi * 0.8 ? 'altında' : 'ortalamada');

            return ResponseService::success([
                'm2_birim_fiyat' => $m2BirimFiyat,
                'formatted' => $formatted,
                'satis_fiyati' => $satisFiyati,
                'brut_m2' => $brutM2,
                'formula' => "{$satisFiyati} / {$brutM2} = {$m2BirimFiyat}",
                'piyasa_analizi' => [
                    'durum' => $durum,
                    'piyasa_ortalamasi' => $piyasaOrtalamasi,
                    'fark_yuzdesi' => round((($m2BirimFiyat - $piyasaOrtalamasi) / $piyasaOrtalamasi) * 100, 2),
                ],
            ], 'm² birim fiyatı başarıyla hesaplandı');
        } catch (\Exception $e) {
            return ResponseService::serverError(
                'Konut metrikleri hesaplanırken bir hata oluştu: ' . $e->getMessage(),
                $e
            );
        }
    }
    /**
     * Public AI Search
     *
     * POST /api/public-ai/ilan-arama
     *
     * Context7: C7-PUBLIC-AI-SEARCH-2025-02-06
     */
    public function publicSearch(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'query' => 'required|string|max:500',
                'location' => 'nullable|string|max:100',
                'budget_min' => 'nullable|numeric|min:0',
                'budget_max' => 'nullable|numeric|min:0',
            ]);

            $semanticService = app(\App\Services\AI\SemanticSearchService::class);
            $query = $validated['query'];

            // 1. Semantik Arama (AI tabanlı)
            $semanticResults = $semanticService->search($query, 15);
            $semanticIds = collect($semanticResults)->pluck('ilan_id')->toArray();

            // 2. Keyword/Filtre Tabanlı Arama (Geleneksel)
            $ilanlar = \App\Models\Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value) // Context7 compliant
                ->when($validated['location'] ?? null, function ($query, $location) {
                    return $query->where(function($q) use ($location) {
                        $q->whereHas('city', function ($sq) use ($location) {
                            $sq->where('ad', 'like', "%{$location}%");
                        })->orWhereHas('ilce', function ($sq) use ($location) {
                            $sq->where('ad', 'like', "%{$location}%");
                        });
                    });
                })
                ->when($validated['budget_min'] ?? null, function ($query, $budget) {
                    return $query->where('fiyat', '>=', $budget);
                })
                ->when($validated['budget_max'] ?? null, function ($query, $budget) {
                    return $query->where('fiyat', '<=', $budget);
                })
                ->where(function ($q) use ($query, $semanticIds) {
                    $q->where('baslik', 'like', "%{$query}%")
                      ->orWhere('aciklama', 'like', "%{$query}%")
                      ->orWhereIn('id', $semanticIds);
                })
                ->with(['il', 'ilce', 'kategori'])
                ->limit(20)
                ->get();

            // Sonuçları semantik skora göre sırala (eğer semantik sonuçlar varsa)
            $scoresMap = collect($semanticResults)->pluck('score', 'ilan_id');
            if (!empty($semanticResults)) {
                $ilanlar = $ilanlar->sortByDesc(function($ilan) use ($scoresMap) {
                    return $scoresMap->get($ilan->id, 0);
                })->values();
            }

            return response()->json([
                'success' => true,
                'query' => $query,
                'search_type' => !empty($semanticResults) ? 'semantic' : 'keyword',
                'results' => $ilanlar->map(function ($ilan) use ($scoresMap) {
                    return [
                        'id' => $ilan->id,
                        'title' => $ilan->baslik,
                        'price' => $ilan->fiyat,
                        'currency' => $ilan->para_birimi,
                        'location' => [
                            'city' => $ilan->il->ad ?? '',
                            'district' => $ilan->ilce->ad ?? '',
                        ],
                        'category' => $ilan->kategori->name ?? '',
                        'ai_score' => $scoresMap ? round($scoresMap->get($ilan->id, 0) * 100) : null
                    ];
                }),
                'count' => $ilanlar->count(),
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            \App\Services\Logging\LogService::error('Public AI search failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Arama yapılamadı: ' . $e->getMessage(),
            ], 500);
        }
    }
}
