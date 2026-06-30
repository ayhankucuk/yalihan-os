<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Response\ResponseService;
use App\Services\SiteService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Site/Apartman API Controller
 * Live search ve site yönetimi için API endpoint'leri
 * Context7 Standard: C7-SITE-API-2025-10-17
 */
class SiteController extends Controller
{
    use ValidatesApiRequests;

    public function __construct(protected SiteService $siteService)
    {
    }

    /**
     * Site/Apartman Live Search
     * GET /admin/api/sites/search
     */
    public function search(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'q' => 'required|string|min:2|max:100',
            'il_id' => 'nullable|exists:iller,id',
            'ilce_id' => 'nullable|exists:ilceler,id',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $query = $request->input('q');
            $ilId = $request->input('il_id');
            $ilceId = $request->input('ilce_id');
            $limit = $request->input('limit', 20);

            $sites = $this->siteService->searchSites($query, $ilId, $ilceId, $limit);

            $formattedSites = $sites->map(function ($site) {
                return $this->siteService->formatSiteForSearch($site);
            });

            return ResponseService::success([
                'data' => $formattedSites,
                'count' => $formattedSites->count(),
                'query' => $query,
            ], 'Site araması başarıyla tamamlandı');
        } catch (\Exception $e) {
            Log::error('Site arama hatası', [
                'error' => $e->getMessage(),
                'query' => $request->input('q'),
                'filters' => $request->only(['il_id', 'ilce_id']),
            ]);

            return ResponseService::serverError('Arama sırasında hata oluştu.', $e);
        }
    }

    /**
     * Yeni Site/Apartman Oluşturma
     * POST /admin/api/sites
     */
    public function store(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'name' => 'required|string|max:255',
            'il_id' => 'required|exists:iller,id',
            'ilce_id' => 'required|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'blok_adi' => 'nullable|string|max:100',
            'adres' => 'nullable|string|max:500',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $site = $this->siteService->createSiteObject($request->all());

            if (!empty($site->display_text)) {
                return ResponseService::error('Bu isimde bir site/apartman zaten mevcut', 409, [
                    'existing_site' => [
                        'id' => $site->id,
                        'name' => $site->name,
                        'display_text' => $site->display_text,
                    ],
                ], 'DUPLICATE_SITE');
            }

            Log::info('Yeni site oluşturuldu', [
                'site_id' => $site->id,
                'name' => $site->name,
                'user_id' => auth()->id(),
            ]);

            return ResponseService::success([
                'id' => $site->id,
                'name' => $site->name,
                'blok_adi' => $site->blok_adi,
                'adres' => $site->adres,
                'full_address' => $this->siteService->buildFullAddress($site),
                'display_text' => $this->siteService->buildDisplayText($site),
            ], 'Site/apartman başarıyla oluşturuldu', 201);
        } catch (\Exception $e) {
            Log::error('Site oluşturma hatası', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return ResponseService::serverError('Site oluşturma sırasında hata oluştu.', $e);
        }
    }

    /**
     * Site detayları
     * GET /admin/api/sites/{id}
     */
    public function show($id)
    {
        try {
            $site = $this->siteService->getSiteDetails((int) $id);
            $siteDetails = $this->siteService->buildSiteDetailsResponse($site);

            return ResponseService::success([
                'data' => $siteDetails,
            ], 'Site detayları başarıyla getirildi');
        } catch (\Exception $e) {
            return ResponseService::notFound('Site bulunamadı');
        }
    }

    /**
     * Site listesi (Admin panel için)
     * GET /admin/api/sites
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $sites = $this->siteService->getSitesList($request->only(['il_id', 'ilce_id']), $perPage);

            // Format data
            $formattedSites = $sites->getCollection()->map(function ($site) {
                return $this->siteService->formatSiteForList($site);
            });

            return ResponseService::success([
                'data' => $formattedSites,
                'pagination' => [
                    'current_page' => $sites->currentPage(),
                    'last_page' => $sites->lastPage(),
                    'per_page' => $sites->perPage(),
                    'total' => $sites->total(),
                ],
            ], 'Site listesi başarıyla getirildi');
        } catch (\Exception $e) {
            Log::error('Site listeleme hatası', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return ResponseService::serverError('Site listesi alınırken hata oluştu.', $e);
        }
    }
}
