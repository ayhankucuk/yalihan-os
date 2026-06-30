<?php

namespace App\Http\Controllers\Analytics;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsReportsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ReportsController
 *
 * Phase 6: Analytics Dashboard & Reporting
 * Context7 Compliance: Uses canonical field rapor_durumu
 */
class ReportsController extends Controller
{
    private AnalyticsReportsService $reportsService;

    public function __construct(AnalyticsReportsService $reportsService)
    {
        $this->reportsService = $reportsService;
    }

    /**
     * POST /api/v1/reports
     * Create a new analytics report
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'rapor_adi' => 'required|string|max:150',
                'parametreler' => 'nullable|array',
            ]);

            $userId = auth()->id();
            $rapor = $this->reportsService->createReport(
                $userId,
                $validated['rapor_adi'],
                $validated['parametreler'] ?? []
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $rapor->id,
                    'rapor_adi' => $rapor->rapor_adi,
                    'rapor_durumu' => $rapor->rapor_durumu, // Context7: hazirlanıyor
                    'baslangic_tarihi' => $rapor->baslangic_tarihi,
                ],
                'message' => 'Rapor oluşturuldu, işleniyor...',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rapor oluşturulamadı',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/reports/{raporId}
     * Get report by ID
     */
    public function show($raporId): JsonResponse
    {
        try {
            $rapor = $this->reportsService->getReportById($raporId);
            
            if (!$rapor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rapor bulunamadı',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $rapor->id,
                    'rapor_adi' => $rapor->rapor_adi,
                    'rapor_durumu' => $rapor->rapor_durumu, // Context7 canonical
                    'aktiflik_durumu' => $rapor->aktiflik_durumu,
                    'baslangic_tarihi' => $rapor->baslangic_tarihi,
                    'bitis_tarihi' => $rapor->bitis_tarihi,
                    'parametreler' => $rapor->parametreler,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rapor alınamadı',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/reports/property/{ilanId}
     * Generate property analysis report
     */
    public function generatePropertyReport($ilanId): JsonResponse
    {
        try {
            $rapor = $this->reportsService->generatePropertyReport($ilanId);

            return response()->json([
                'success' => true,
                'data' => $rapor,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mülk raporu oluşturulamadı',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/reports/market-trend
     * Generate market trend report
     */
    public function generateMarketTrendReport(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'il_id' => 'required|integer',
                'ilce_id' => 'required|integer',
                'baslangic_tarihi' => 'required|date',
                'bitis_tarihi' => 'required|date|after_or_equal:baslangic_tarihi',
            ]);

            $rapor = $this->reportsService->generateMarketTrendReport(
                $validated['il_id'],
                $validated['ilce_id'],
                $validated['baslangic_tarihi'],
                $validated['bitis_tarihi']
            );

            return response()->json([
                'success' => true,
                'data' => $rapor,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pazar trend raporu oluşturulamadı',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/reports/competitor/{ilanId}
     * Generate competitor analysis report
     */
    public function generateCompetitorReport($ilanId, Request $request): JsonResponse
    {
        try {
            $radius = $request->input('radius', 2);
            $rapor = $this->reportsService->generateCompetitorReport($ilanId, $radius);

            return response()->json([
                'success' => true,
                'data' => $rapor,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rakip raporu oluşturulamadı',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/v1/reports/{raporId}/send
     * Mark report as sent (rapor_durumu='gonderildi')
     */
    public function markAsSent($raporId, Request $request): JsonResponse
    {
        try {
            $dosyaYolu = $request->input('dosya_yolu');
            $rapor = $this->reportsService->markReportAsSent($raporId, $dosyaYolu);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $rapor->id,
                    'rapor_durumu' => $rapor->rapor_durumu, // Context7: gonderildi
                    'bitis_tarihi' => $rapor->bitis_tarihi,
                    'dosya_yolu' => $rapor->dosya_yolu,
                ],
                'message' => 'Rapor gönderildi',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rapor durumu güncellenemedi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
