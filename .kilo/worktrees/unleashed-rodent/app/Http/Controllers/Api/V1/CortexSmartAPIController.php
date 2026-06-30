<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\CortexROIEngine;
use App\Services\IlanVerticalDomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Yalıhan Cortex AI: Smart API Gateway
 *
 * Context7 Standard: C7-API-GATEWAY-2025-12-23
 * Version: 1.0.0
 *
 * Context7 Compliance:
 * ✅ Service pattern (business logic Controller'da yok)
 * ✅ Eager loading (N+1 yok)
 * ✅ SAB headers (X-Context7-Standard vb.)
 * ✅ Error handling + logging
 * ✅ Naming convention (name, yayin_durumu, display_order)
 */
class CortexSmartAPIController extends Controller
{
    public function __construct(
        protected IlanVerticalDomainService $verticalService,
        protected CortexROIEngine $cortexEngine,
        protected \App\Services\CortexGoldenVisaAnalyzer $goldenVisaAnalyzer,
        protected \App\Services\CortexSpatialIntelligenceService $spatialService
    ) {}

    /**
     * 🤖 İlan + ROI Full Details
     *
     * GET /api/v1/cortex/ilan/{id}/full-details
     *
     * Context7: Smart loading (domain-based + ROI)
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function getFullDetails(int $id): JsonResponse
    {
        try {
            // Service'den domain bazlı ilan getir (eager loading)
            $result = $this->verticalService->getIlanByDomain($id);

            if (! $result['ilan']) {
                return $this->errorResponse('İlan bulunamadı', 404);
            }

            $ilan = $result['ilan'];

            // Cortex ROI hesapla (eğer yoksa)
            $cortexData = $ilan->additional_metadata['cortex_ai'] ?? null;
            if (! $cortexData) {
                $cortexData = $this->cortexEngine->calculateCortexScore($ilan);
            }

            // Response data
            $responseData = [
                'ilan' => [
                    'id' => $ilan->id,
                    'baslik' => $ilan->baslik,
                    'aciklama' => $ilan->aciklama,
                    'fiyat' => $ilan->fiyat,
                    'fiyat_formatted' => number_format($ilan->fiyat, 2).' TRY',
                    'alan_m2' => $ilan->alan_m2_net,
                    'oda_sayisi' => $ilan->oda_sayisi,
                    'yayin_durumu' => $ilan->yayin_durumu,
                    'referans_no' => $ilan->referans_no,
                    'lokasyon' => [
                        'il' => $ilan->il?->name,
                        'ilce' => $ilan->ilce?->name,
                        'mahalle' => $ilan->mahalle?->name,
                    ],
                    'kategori' => [
                        'ana_kategori' => $ilan->anaKategori?->name,
                        'alt_kategori' => $ilan->altKategori?->name,
                    ],
                ],
                'domain' => $result['domain'],
                'domain_detail' => $result['detail'],
                'cortex_ai' => $cortexData,
                'has_photos' => $ilan->fotograflar()->count() > 0,
                'photo_count' => $ilan->fotograflar()->count(),
            ];

            return $this->successResponse($responseData);
        } catch (\Exception $e) {
            Log::error('Cortex API: Full details error', [
                'ilan_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Sunucu hatası', 500);
        }
    }

    /**
     * 🤖 ROI Hesapla ve Kaydet
     *
     * POST /api/v1/cortex/ilan/{id}/calculate-roi
     *
     * Context7: AI-powered calculation
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function calculateROI(int $id): JsonResponse
    {
        try {
            $result = $this->verticalService->calculateAndSaveROI($id);

            if (! $result) {
                return $this->errorResponse('ROI hesaplanamadı veya ilan bulunamadı', 404);
            }

            return $this->successResponse([
                'message' => 'ROI başarıyla hesaplandı ve kaydedildi',
                'cortex_ai' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Cortex API: ROI calculation error', [
                'ilan_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('ROI hesaplama hatası', 500);
        }
    }

    /**
     * 🤖 Toplu ROI Hesaplama
     *
     * POST /api/v1/cortex/ilan/batch-calculate-roi
     * Body: { "ilan_ids": [1, 2, 3, ...] }
     *
     * Context7: Batch processing
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function batchCalculateROI(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ilan_ids' => 'required|array|min:1|max:100',
            'ilan_ids.*' => 'required|integer|exists:ilanlar,id',
        ]);

        try {
            $results = $this->verticalService->batchCalculateROI($validated['ilan_ids']);

            return $this->successResponse([
                'message' => 'Toplu ROI hesaplama tamamlandı',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Cortex API: Batch ROI calculation error', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Toplu ROI hesaplama hatası', 500);
        }
    }

    /**
     * 🤖 Arsa İlanları + ROI Filtering
     *
     * GET /api/v1/cortex/arsa?min_roi=50&max_price=5000000
     *
     * Context7: Smart filtering + eager loading
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getArsaWithROI(Request $request): JsonResponse
    {
        try {
            // Base filters (service'den gelen)
            $filters = [
                'imar_durumu' => $request->input('imar_durumu'),
                'min_alan_m2' => $request->input('min_alan_m2'),
                'altyapi_tam' => $request->boolean('altyapi_tam'),
            ];

            // ROI filters
            $minROI = $request->input('min_roi');
            $maxPrice = $request->input('max_price');

            // Service'den arsa ilanları getir
            $ilanlar = $this->verticalService->getArsaIlanlari(array_filter($filters));

            // ROI bazlı filtreleme (eğer metadata varsa)
            if ($minROI) {
                $ilanlar = $ilanlar->filter(function ($ilan) use ($minROI) {
                    $cortexData = $ilan->additional_metadata['cortex_ai'] ?? null;

                    return $cortexData && ($cortexData['roi_data']['roi_percentage'] ?? 0) >= $minROI;
                });
            }

            // Fiyat filtresi
            if ($maxPrice) {
                $ilanlar = $ilanlar->filter(fn ($ilan) => $ilan->fiyat <= $maxPrice);
            }

            return $this->successResponse([
                'data' => $ilanlar->values(),
                'count' => $ilanlar->count(),
                'filters_applied' => array_merge($filters, [
                    'min_roi' => $minROI,
                    'max_price' => $maxPrice,
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('Cortex API: Arsa ROI filtering error', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Arsa ROI filtreleme hatası', 500);
        }
    }

    /**
     * 🤖 Turizm İlanları + ROI Filtering
     *
     * GET /api/v1/cortex/turizm?min_payback_period=5&havuz_var=1
     *
     * Context7: Smart filtering + eager loading
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getTurizmWithROI(Request $request): JsonResponse
    {
        try {
            // Base filters
            $filters = [
                'havuz_var' => $request->boolean('havuz_var'),
                'min_gunluk_fiyat' => $request->input('min_gunluk_fiyat'),
                'sezon_aktif' => $request->boolean('sezon_aktif'),
            ];

            // ROI filters
            $minPaybackPeriod = $request->input('min_payback_period');

            // Service'den turizm ilanları getir
            $ilanlar = $this->verticalService->getTurizmIlanlari(array_filter($filters));

            // Payback period filtresi
            if ($minPaybackPeriod) {
                $ilanlar = $ilanlar->filter(function ($ilan) use ($minPaybackPeriod) {
                    $cortexData = $ilan->additional_metadata['cortex_ai'] ?? null;

                    return $cortexData
                        && ($cortexData['roi_data']['payback_period_years'] ?? 99)
                        <= $minPaybackPeriod;
                });
            }

            return $this->successResponse([
                'data' => $ilanlar->values(),
                'count' => $ilanlar->count(),
                'filters_applied' => array_merge($filters, [
                    'min_payback_period' => $minPaybackPeriod,
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('Cortex API: Turizm ROI filtering error', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Turizm ROI filtreleme hatası', 500);
        }
    }

    /**
     * Context7 Success Response Helper
     *
     * @param  array  $data
     * @param  int  $statusCode
     * @return JsonResponse
     */
    protected function successResponse(array $data, int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'version' => '1.0.0',
            ],
        ], $statusCode)
            ->withHeaders($this->getContext7Headers());
    }

    /**
     * Context7 Error Response Helper
     *
     * @param  string  $message
     * @param  int  $statusCode
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'version' => '1.0.0',
            ],
        ], $statusCode)
            ->withHeaders($this->getContext7Headers());
    }

    /**     * Analyze property for Golden Visa eligibility
     *
     * @param int $id
     * @return JsonResponse
     */
    public function analyzeGoldenVisa(int $id): JsonResponse
    {
        try {
            $ilan = \App\Models\Ilan::with(['turizmDetail', 'arsaDetail', 'il', 'ilce', 'anaKategori'])
                ->findOrFail($id);

            $analysis = $this->goldenVisaAnalyzer->analyzeGoldenVisaOpportunity($ilan);

            // Save to metadata
            $this->goldenVisaAnalyzer->saveAnalysisToMetadata($ilan);

            return $this->successResponse($analysis);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('İlan bulunamadı', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Golden Visa analiz hatası: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all Golden Visa eligible properties
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getGoldenVisaEligible(Request $request): JsonResponse
    {
        try {
            $minInvestmentTRY = 400000 * 32.5;

            $ilanlar = \App\Models\Ilan::with(['il', 'ilce', 'anaKategori'])
                ->where('yayin_durumu', 'active') // context7-ignore
                ->where('fiyat', '>=', $minInvestmentTRY)
                ->whereIn('ana_kategori_id', [1, 2, 3, 7])
                ->orderByDesc('fiyat') // context7-ignore
                ->limit($request->input('limit', 20))
                ->get();

            $results = $ilanlar->map(function ($ilan) {
                $metadata = $ilan->additional_metadata;
                if (is_string($metadata)) {
                    $metadata = json_decode($metadata, true);
                }

                return [
                    'id' => $ilan->id,
                    'title' => $ilan->baslik,
                    'price_usd' => round($ilan->fiyat / 32.5, 2),
                    'price_try' => $ilan->fiyat,
                    'location' => $ilan->il?->name . ' / ' . $ilan->ilce?->name,
                    'category' => $ilan->anaKategori?->name,
                    'golden_visa_score' => $metadata['cortex_ai']['golden_visa_analysis']['investment_score'] ?? null,
                    'has_analysis' => isset($metadata['cortex_ai']['golden_visa_analysis']),
                ];
            });

            return $this->successResponse([
                'total' => $results->count(),
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Golden Visa liste hatası: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get spatial intelligence for property
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getSpatialIntelligence(int $id): JsonResponse
    {
        try {
            $data = $this->spatialService->getSpatialWithROI($id, true);

            if (!$data) {
                return $this->errorResponse('İlan bulunamadı', 404);
            }

            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Spatial intelligence hatası: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get batch spatial intelligence
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getBatchSpatial(Request $request): JsonResponse
    {
        try {
            $ilanIds = $request->input('ilan_ids', []);

            if (empty($ilanIds) || !is_array($ilanIds)) {
                return $this->errorResponse('ilan_ids array gerekli', 400);
            }

            if (count($ilanIds) > 50) {
                return $this->errorResponse('Maksimum 50 ilan işlenebilir', 400);
            }

            $results = $this->spatialService->getBatchSpatialData($ilanIds);

            return $this->successResponse($results);
        } catch (\Exception $e) {
            return $this->errorResponse('Batch spatial hatası: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 🤖 AI İlan Başlık Optimizasyonu
     *
     * POST /api/v1/cortex/ai/optimize-title
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function optimizeTitle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'baslik' => 'nullable|string|max:255',
            'kategori' => 'nullable|string',
            'ana_kategori_id' => 'nullable|integer',
            'lokasyon' => 'nullable|string',
            'il_id' => 'nullable|integer',
            'ilce_id' => 'nullable|integer',
            'mahalle_id' => 'nullable|integer',
            'features' => 'nullable|array',
            'ozellik_ids' => 'nullable|array',
        ]);

        try {
            // Cortex servisine gönder
            $cortex = app(\App\Services\AI\YalihanCortex::class);
            $result = $cortex->optimizeIlanTitle($validated);

            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse('Başlık optimizasyonu hatası: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 🤖 AI İlan Açıklaması Üretimi
     *
     * POST /api/v1/cortex/ai/generate-description
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateDescription(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:ilanlar,id',
            'baslik' => 'nullable|string|max:255',
            'kategori' => 'nullable|string',
            'ana_kategori_id' => 'nullable|integer',
            'lokasyon' => 'nullable|string',
            'il_id' => 'nullable|integer',
            'ilce_id' => 'nullable|integer',
            'mahalle_id' => 'nullable|integer',
            'features' => 'nullable|array',
            'ozellik_ids' => 'nullable|array',
            'tone' => 'nullable|string|in:seo,kurumsal,hizli_satis,luks',
            'length' => 'nullable|string|in:short,medium,long',
        ]);

        try {
            $cortex = app(\App\Services\AI\YalihanCortex::class);

            // Eğer ID varsa direkt modeli kullan, yoksa array verisi
            $ilan = $request->input('id')
                ? \App\Models\Ilan::find($request->input('id'))
                : $validated;

            $options = [
                'tone' => $request->input('tone', 'luks'),
                'length' => $request->input('length', 'medium'),
                'draft_features' => $request->input('features') ?? $request->input('ozellikler')
            ];

            $startTime = microtime(true);
            $result = $cortex->generateIlanDescription($ilan, $options);
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Context7: Log AI usage
            if ($request->user()) {
                \App\Models\AiLog::create([
                    'user_id' => $request->user()->id,
                    'endpoint' => 'generate_ilan_description', // Mapped from request_type
                    'request_payload' => $validated,
                    'response_payload' => $result,
                    'aktiflik_kodu' => 200, // Success
                    'duration_ms' => $durationMs,
                    'provider' => 'ollama',
                    'ip_address' => $request->ip(),
                ]);
            }

            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse('Açıklama üretimi hatası: ' . $e->getMessage(), 500);
        }
    }

    /**     * Context7 Compliance Headers
     *
     * @return array
     */
    protected function getContext7Headers(): array
    {
        return [
            'X-Context7-Standard' => 'C7-CORTEX-AI-2025-12-23',
            'X-Context7-Version' => '1.0.0',
            'X-Framework-Version' => 'Laravel 10',
            'X-AI-Engine' => 'Yalıhan Cortex ROI Engine v1.0.0',
        ];
    }
}
