<?php

namespace App\Http\Controllers\Analytics;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * DashboardController
 *
 * Phase 6: Analytics Dashboard & Reporting
 * Context7 Compliance: Uses canonical fields analiz_durumu, siralama_sirasi, varsayilan_mi
 */
class DashboardController extends Controller
{
    private AnalyticsDashboardService $dashboardService;

    public function __construct(AnalyticsDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * GET /api/v1/dashboard/filters
     * Get all filters for authenticated user
     */
    public function getFilters(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $filtreler = $this->dashboardService->getUserFilters($userId);

            return response()->json([
                'success' => true,
                'data' => $filtreler,
                'count' => count($filtreler),
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Filtreler alınamadı',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/dashboard/filters
     * Create a new dashboard filter
     */
    public function createFilter(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'filtre_adi' => 'required|string|max:100',
                'filtre_kurallari' => 'nullable|array',
            ]);

            $userId = auth()->id();
            $filtre = $this->dashboardService->createFilter(
                $userId,
                $validated['filtre_adi'],
                $validated['filtre_kurallari'] ?? []
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $filtre->id,
                    'filtre_adi' => $filtre->filtre_adi,
                    'analiz_durumu' => $filtre->analiz_durumu, // Context7: aktif
                    'varsayilan_mi' => $filtre->varsayilan_mi,
                    'siralama_sirasi' => $filtre->siralama_sirasi,
                ],
                'message' => 'Filtre oluşturuldu',
            ], 201);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Filtre oluşturulamadı',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/dashboard/filters/default
     * Get default filter for user
     */
    public function getDefaultFilter(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $filtre = $this->dashboardService->getDefaultFilter($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $filtre->id,
                    'filtre_adi' => $filtre->filtre_adi,
                    'analiz_durumu' => $filtre->analiz_durumu, // Context7: aktif
                    'varsayilan_mi' => $filtre->varsayilan_mi, // Context7 canonical
                    'filtre_kurallari' => $filtre->filtre_kurallari,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Varsayılan filtre alınamadı',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/v1/dashboard/filters/{filtreId}/archive
     * Archive a filter (set analiz_durumu='sonlandırıldı')
     */
    public function archiveFilter($filtreId): JsonResponse
    {
        try {
            $userId = auth()->id();
            $filtre = $this->dashboardService->archiveFilter($userId, $filtreId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $filtre->id,
                    'analiz_durumu' => $filtre->analiz_durumu, // Context7: sonlandirildi
                    'aktiflik_durumu' => $filtre->aktiflik_durumu,
                ],
                'message' => 'Filtre arşivlendi',
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Filtre arşivlenemedi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/v1/dashboard/filters/{filtreId}/lock
     * Lock a filter (set analiz_durumu='kilitli')
     */
    public function lockFilter($filtreId): JsonResponse
    {
        try {
            $userId = auth()->id();
            $filtre = $this->dashboardService->lockFilter($userId, $filtreId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $filtre->id,
                    'analiz_durumu' => $filtre->analiz_durumu, // Context7: kilitli
                ],
                'message' => 'Filtre kilitlendi',
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Filtre kilitlenemedi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/dashboard/apply-filter/{filtreId}
     * Apply filter to listings
     */
    public function applyFilter($filtreId, Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $ilans = \App\Models\Ilan::where('user_id', $userId)
                ->where('aktiflik_durumu', true)
                ->limit(100)
                ->get()
                ->toArray();

            $ilans = $this->dashboardService->applyFilterToListings(
                $userId,
                $filtreId,
                $ilans
            );

            return response()->json([
                'success' => true,
                'data' => $ilans,
                'count' => count($ilans),
                'message' => 'Filtre uygulandı',
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Filtre uygulanamadı',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
