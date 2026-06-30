<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Kisi; // Keep Kisi model for findOrFail
use App\Services\Response\ResponseService;
use App\Services\CRM\KisiAnalyticsService; // Added
use App\Services\CRM\KisiRegistrationService; // Added, though not used in the provided snippet
use App\Traits\ValidatesApiRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException; // Added
use Illuminate\Http\JsonResponse; // Keep for other methods
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Kişi API Controller
 *
 * Context7 Standardı: C7-KISI-API-CONTROLLER-2025-10-11
 *
 * CRM ve İlan geçmişi API endpoint'leri
 */
class KisiController extends Controller
{
    use ValidatesApiRequests;

    private \App\Services\AI\YalihanCortex $cortex;
    private KisiRegistrationService $registrationService;

    public function __construct(
        \App\Services\AI\YalihanCortex $cortex,
        KisiRegistrationService $registrationService
    ) {
        $this->cortex = $cortex;
        $this->registrationService = $registrationService;
    }

    /**
     * Kişinin ilan geçmişini getir
     *
     * GET /api/kisiler/{id}/ilan-gecmisi
     */
    public function getIlanGecmisi(int $id): JsonResponse
    {
        try {
            // Note: History analysis is now routed through Cortex for authority consolidation
            $result = $this->cortex->analyzeCustomerHistory($id);
            
            return ResponseService::success($result);
        } catch (\Exception $e) {
            Log::error('Kişi ilan geçmişi hatası', ['kisi_id' => $id, 'error' => $e->getMessage()]);
            return ResponseService::serverError('İlan geçmişi yüklenemedi', $e);
        }
    }

    /**
     * Kişi profil özeti
     *
     * GET /api/kisiler/{id}/profil
     */
    public function getProfil(int $id): JsonResponse
    {
        try {
            // Domain Decision (Audit/Profile) via Scoring Service
            $result = app(\App\Services\CRM\KisiScoringService::class)->performAudit($id);

            return ResponseService::success($result);
        } catch (\Exception $e) {
            return ResponseService::serverError('Kişi profili yüklenemedi', $e);
        }
    }

    /**
     * Kişi arama (Context7 Live Search için)
     *
     * GET /api/kisiler/search
     */
    public function search(Request $request): JsonResponse
    {
        try {
            return ResponseService::success([
                'kisiler' => $this->registrationService->search($request),
            ]);
        } catch (\Exception $e) {
            Log::error('Kişi arama hatası', ['query' => $request->get('q'), 'error' => $e->getMessage()]);
            return ResponseService::serverError('Kişi araması sırasında hata oluştu', $e);
        }
    }

    /**
     * Kişi oluştur (Modal'dan)
     *
     * POST /api/kisiler
     */
    public function store(\App\Http\Requests\Api\KisiStoreRequest $request): JsonResponse
    {
        try {
            $kisi = $this->registrationService->register($request->validated(), auth()->id());

            return ResponseService::success([
                'id' => $kisi->id,
                'ad' => $kisi->ad,
                'soyad' => $kisi->soyad,
            ], 'Kişi başarıyla eklendi', 201);
        } catch (\Exception $e) {
            Log::error('Kişi oluşturma hatası', ['error' => $e->getMessage(), 'data' => $request->all()]);
            return ResponseService::serverError('Kişi oluşturulamadı', $e);
        }
    }

    /**
     * AI İlan Geçmişi Analizi
     *
     * GET /api/kisiler/{id}/ai-gecmis-analiz
     */
    public function getAIGecmisAnaliz(int $id): JsonResponse
    {
        try {
            // CANONICAL: Routing through Cortex Capability Authority
            $result = $this->cortex->analyzeCustomerHistory($id);

            if (isset($result['success']) && !$result['success']) {
                if (($result['data']['analysis']['has_history'] ?? null) === false) {
                    return ResponseService::error(
                        $result['message'] ?? 'Kişinin geçmiş kaydı bulunamadı',
                        200,
                        ['analysis' => $result['data']['analysis']]
                    );
                }
                return ResponseService::notFound($result['message'] ?? 'AI geçmiş analizi bulunamadı');
            }

            return ResponseService::success($result['data'] ?? $result, 'AI ilan geçmişi analizi başarıyla tamamlandı');
        } catch (\Exception $e) {
            Log::error('AI Geçmiş Analizi hatası', ['kisi_id' => $id, 'error' => $e->getMessage()]);
            return ResponseService::serverError('AI geçmiş analizi sırasında hata oluştu', $e);
        }
    }
}
