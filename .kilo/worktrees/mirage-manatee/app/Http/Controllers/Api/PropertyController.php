<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\Integrations\TKGMService;
use App\Services\Integrations\WikiMapiaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Property API Controller
 *
 * Context7: İlan/Property ile ilgili API endpoint'leri
 * Yalıhan Bekçi: TKGM Auto-Fill Integration (2025-12-02)
 */
class PropertyController extends Controller
{
    /**
     * TKGM Service instance
     */
    protected TKGMService $tkgmService;

    protected WikiMapiaService $wikiMapiaService;

    /**
     * Constructor
     */
    public function __construct(TKGMService $tkgmService, WikiMapiaService $wikiMapiaService)
    {
        $this->tkgmService = $tkgmService;
        $this->wikiMapiaService = $wikiMapiaService;
    }

    /**
     * TKGM Parsel Sorgulama (AJAX Endpoint)
     *
     * Gemini AI Önerisi: Ada/Parsel girildiğinde arsa bilgilerini otomatik doldurmak
     * Context7: imar_durumu → imar_durumu
     *
     * POST /api/properties/tkgm-lookup
     *
     * Request Body:
     * {
     *   "il": "Muğla",
     *   "ilce": "Bodrum",
     *   "ada": "1234",
     *   "parsel": "5"
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "data": {
     *     "ada_no": "1234",
     *     "parsel_no": "5",
     *     "alan_m2": 1500.50,
     *     "nitelik": "Arsa",
     *     "imar_durumu": "İmarlı",
     *     "kaks": 0.30,
     *     "taks": 0.25,
     *     "gabari": 7.50,
     *     "center_lat": 37.0361,
     *     "center_lng": 27.4305,
     *     "enlem": 37.0361,
     *     "boylam": 27.4305,
     *     "yola_cephe": true,
     *     "altyapi_elektrik": true,
     *     "altyapi_su": true,
     *     "altyapi_dogalgaz": false
     *   },
     *   "message": "Parsel bilgileri başarıyla alındı"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tkgmLookup(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'il' => 'required|string|max:100',
            'ilce' => 'required|string|max:100',
            'ada' => 'required|string|max:50',
            'parsel' => 'required|string|max:50',
            'ilan_id' => 'nullable|integer|exists:ilanlar,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasyon hatası',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->tkgmService->queryParcel(
                $request->il,
                $request->ilce,
                $request->ada,
                $request->parsel
            );

            if (!$result || !$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parsel bilgileri bulunamadı. Lütfen Ada ve Parsel numaralarını kontrol edin.',
                    'data' => null,
                ], 404);
            }

            $nearbyPlaces = [];
            if (
                isset($result['data']['center_lat']) &&
                isset($result['data']['center_lng']) &&
                is_numeric($result['data']['center_lat']) &&
                is_numeric($result['data']['center_lng'])
            ) {
                $nearbyPlaces = $this->wikiMapiaService->searchNearbyPlaces(
                    (float) $result['data']['center_lat'],
                    (float) $result['data']['center_lng']
                );
            }

            // If ilan_id provided, store POIs on the listing
            if ($request->filled('ilan_id') && $nearbyPlaces) {
                $ilan = Ilan::find($request->ilan_id);
                if ($ilan) {
                    $ilan->nearby_places = $nearbyPlaces;
                    $ilan->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Parsel bilgileri başarıyla alındı',
                'data' => array_merge($result['data'], [
                    'nearby_places' => $nearbyPlaces,
                ]),
            ]);

        } catch (\Exception $e) {
            Log::error('TKGM Lookup Error', [
                'request' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Parsel sorgulama sırasında bir hata oluştu: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * TKGM Health Check
     *
     * GET /api/properties/tkgm-health
     *
     * Response:
     * {
     *   "success": true,
     *   "durum": "mock",
     *   "message": "TKGM Service çalışıyor (Mock Mode)"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tkgmHealth()
    {
        $health = $this->tkgmService->healthCheck();

        return response()->json($health, $health['success'] ? 200 : 503);
    }
}

