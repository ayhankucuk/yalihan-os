<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Il;
use App\Models\Ilan;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Services\Logging\LogService;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ListingSearchController extends Controller
{
    use ValidatesApiRequests;

    protected $searchService;

    public function __construct(\App\Services\Admin\IlanSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function search(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'q' => 'nullable|string',
            'type' => 'nullable|in:owner,phone,site,advisor,all', // context7-ignore
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $limit = (int) ($request->input('limit', 20));

        try {
            // ✅ SAB: Use centralized IlanSearchService
            $results = $this->searchService->search($request)
                ->limit($limit)
                ->get();

            // Format response data
            $formattedResults = $results->map(function ($ilan) {
                return [
                    'id' => $ilan->id,
                    'baslik' => $ilan->baslik,
                    'slug' => $ilan->slug,
                    'yayin_durumu' => $ilan->yayin_durumu,
                    'state' => $ilan->yayin_durumu, // context7-ignore
                    'fiyat' => $ilan->fiyat,
                    'para_birimi' => $ilan->para_birimi,
                    'site_id' => $ilan->site_id,
                    'ilan_sahibi_id' => $ilan->ilan_sahibi_id,
                    'danisman_id' => $ilan->danisman_id,
                    // Relation access (Standard Eloquent)
                    'site_name' => $ilan->site->name ?? null,
                    'sahibi_ad' => $ilan->ilanSahibi->ad ?? null,
                    'sahibi_soyad' => $ilan->ilanSahibi->soyad ?? null,
                    'sahibi_telefon' => $ilan->ilanSahibi->telefon ?? null,
                    'danisman_ad' => $ilan->danisman->name ?? null,
                    'danisman_email' => $ilan->danisman->email ?? null,
                ];
            });

            return ResponseService::success([
                'count' => $formattedResults->count(),
                'data' => $formattedResults,
            ], 'İlan araması başarıyla tamamlandı');
        } catch (\Throwable $e) {
            LogService::api('/api/search', [], null, null);
            LogService::error('ListingSearchController@search error', [
                'request' => $request->all(),
                'message' => $e->getMessage()
            ]);

            return ResponseService::serverError('İlan arama sırasında hata oluştu.', $e);
        }
    }

    // Lokasyon metodları (API endpoint'leri için)

    public function getProvinces()
    {
        try {
            $provinces = Il::select('id', 'il_adi as il')
                ->orderBy('il_adi') // context7-ignore
                ->get();

            return ResponseService::success([
                'data' => $provinces,
            ], 'İller başarıyla getirildi');
        } catch (\Throwable $e) {
            Log::error('ListingSearchController@getProvinces error: '.$e->getMessage());

            return ResponseService::serverError('İller yüklenirken hata oluştu.', $e);
        }
    }

    public function getDistricts($provinceId)
    {
        try {
            $districts = Ilce::select('id', 'ilce_adi as ilce')
                ->where('il_id', $provinceId)
                ->orderBy('ilce_adi') // context7-ignore
                ->get();

            return ResponseService::success([
                'data' => $districts,
            ], 'İlçeler başarıyla getirildi');
        } catch (\Throwable $e) {
            // ✅ STANDARDIZED: Using LogService
            LogService::api('/api/districts', [], null, null);
            LogService::error('ListingSearchController@getDistricts error', [
                'province_id' => $provinceId,
            ]);

            return ResponseService::serverError('İlçeler yüklenirken hata oluştu.', $e);
        }
    }

    public function getNeighborhoods($districtId)
    {
        try {
            $neighborhoods = Mahalle::select('id', 'mahalle_adi as mahalle')
                ->where('ilce_id', $districtId)
                ->orderBy('mahalle_adi') // context7-ignore
                ->get();

            return ResponseService::success([
                'data' => $neighborhoods,
            ], 'Mahalleler başarıyla getirildi');
        } catch (\Throwable $e) {
            // ✅ STANDARDIZED: Using LogService
            LogService::api('/api/neighborhoods', [], null, null);
            LogService::error('ListingSearchController@getNeighborhoods error', [
                'district_id' => $districtId,
            ]);

            return ResponseService::serverError('Mahalleler yüklenirken hata oluştu.', $e);
        }
    }
}
