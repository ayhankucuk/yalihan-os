<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\IlanVerticalDomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Context7 Standard: Vertical Domain API Controller
 *
 * X-Context7-Standard: C7-SCHEMA-REFACTOR-2025-12-23
 * X-Context7-Version: 1.0.0
 * X-Framework-Version: Laravel 10
 *
 * Bu controller, dikey alan ayrıştırma mimarisinde ilanları yönetir.
 * Tüm response'lar SabComplianceMiddleware tarafından kontrol edilir.
 *
 * @version 1.0.0
 * @since 2025-12-23
 */
class IlanVerticalDomainController extends Controller
{
    protected IlanVerticalDomainService $service;

    public function __construct(IlanVerticalDomainService $service)
    {
        $this->service = $service;

        // SabComplianceMiddleware (API rotalarında tanımlı olmalı)
        $this->middleware('context7.compliance');
    }

    /**
     * Turizm/Yazlık ilanlarını listele
     *
     * GET /api/v1/ilanlar/turizm
     *
     * Query Parameters:
     * - havuz_var: boolean (opsiyonel)
     * - min_gunluk_fiyat: numeric (opsiyonel)
     * - sezon_aktif: boolean (opsiyonel)
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getTurizmIlanlari(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'havuz_var' => 'sometimes|boolean',
            'min_gunluk_fiyat' => 'sometimes|numeric|min:0',
            'sezon_aktif' => 'sometimes|boolean',
        ]);

        $ilanlar = $this->service->getTurizmIlanlari($validated);

        return response()->json([
            'success' => true,
            'data' => $ilanlar,
            'count' => $ilanlar->count(),
            'domain' => 'turizm',
            'filters_applied' => $validated,
        ]);
    }

    /**
     * Arsa/Arazi ilanlarını listele
     *
     * GET /api/v1/ilanlar/arsa
     *
     * Query Parameters:
     * - imar_durumu: string (opsiyonel)
     * - min_alan_m2: numeric (opsiyonel)
     * - altyapi_tam: boolean (opsiyonel)
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getArsaIlanlari(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'imar_durumu' => 'sometimes|string|max:100',
            'min_alan_m2' => 'sometimes|numeric|min:0',
            'altyapi_tam' => 'sometimes|boolean',
        ]);

        $ilanlar = $this->service->getArsaIlanlari($validated);

        return response()->json([
            'success' => true,
            'data' => $ilanlar,
            'count' => $ilanlar->count(),
            'domain' => 'arsa',
            'filters_applied' => $validated,
        ]);
    }

    /**
     * Ticari/İşyeri ilanlarını listele
     *
     * GET /api/v1/ilanlar/ticari
     *
     * Query Parameters:
     * - isyeri_tipi: string (opsiyonel)
     * - ruhsat_aktif: boolean (opsiyonel)
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getTicariIlanlari(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'isyeri_tipi' => 'sometimes|string|max:255',
            'ruhsat_aktif' => 'sometimes|boolean',
        ]);

        $ilanlar = $this->service->getTicariIlanlari($validated);

        return response()->json([
            'success' => true,
            'data' => $ilanlar,
            'count' => $ilanlar->count(),
            'domain' => 'ticari',
            'filters_applied' => $validated,
        ]);
    }

    /**
     * Portal senkronize ilanları listele
     *
     * GET /api/v1/ilanlar/portal-sync
     *
     * Query Parameters:
     * - portal: string (opsiyonel) - sahibinden, emlakjet, hepsiemlak, zingat, hurriyetemlak
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getPortalSyncIlanlari(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'portal' => 'sometimes|string|in:sahibinden,emlakjet,hepsiemlak,zingat,hurriyetemlak',
        ]);

        $ilanlar = $this->service->getPortalSyncIlanlari($validated['portal'] ?? null);

        return response()->json([
            'success' => true,
            'data' => $ilanlar,
            'count' => $ilanlar->count(),
            'portal' => $validated['portal'] ?? 'all',
        ]);
    }

    /**
     * İlan detayını tüm domain bilgileriyle getir
     *
     * GET /api/v1/ilanlar/{id}/full-details
     *
     * Context7: Tüm dikey tabloları eager load eder (tek sorgu)
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function getIlanFullDetails(int $id): JsonResponse
    {
        $ilan = $this->service->getIlanWithAllDetails($id);

        if (! $ilan) {
            return response()->json([
                'success' => false,
                'message' => 'İlan bulunamadı',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'ilan' => $ilan,
                'has_turizm_detail' => ! is_null($ilan->turizmDetail),
                'has_arsa_detail' => ! is_null($ilan->arsaDetail),
                'has_ticari_detail' => ! is_null($ilan->ticariDetail),
                'has_portal_sync' => ! is_null($ilan->portalSync),
            ],
        ]);
    }

    /**
     * İlan detayını domain bazlı getir (smart loading)
     *
     * GET /api/v1/ilanlar/{id}/by-domain
     *
     * Context7: Sadece gerekli domain detayını yükler (optimize)
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function getIlanByDomain(int $id): JsonResponse
    {
        $result = $this->service->getIlanByDomain($id);

        if (! $result['ilan']) {
            return response()->json([
                'success' => false,
                'message' => 'İlan bulunamadı',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'ilan' => $result['ilan'],
                'detected_domain' => $result['domain'],
                'domain_detail' => $result['detail'],
            ],
        ]);
    }
}
