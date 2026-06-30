<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingController extends Controller
{
    use ValidatesApiRequests;

    /**
     * Nominatim geocoding proxy (CORS sorunu çözümü)
     */
    public function search(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'q' => 'required|string|min:3|max:255',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $query = $request->input('q');

            // Nominatim API'ye proxy isteği
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'YalihanEmlak/1.0 (Real Estate Management System)',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 5,
                    'countrycodes' => 'tr', // Türkiye sınırlı
                    'addressdetails' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return ResponseService::success([
                    'results' => $data,
                    'query' => $query,
                    'count' => count($data),
                ], 'Adres araması başarıyla tamamlandı');
            } else {
                return ResponseService::serverError('Geocoding servisi yanıt vermiyor');
            }

        } catch (\Exception $e) {
            Log::error('Geocoding hatası: '.$e->getMessage());

            return ResponseService::serverError('Adres arama başarısız.', $e);
        }
    }

    /**
     * Reverse geocoding (koordinat → adres)
     */
    public function reverse(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $lat = $request->input('lat');
            $lon = $request->input('lon');

            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'YalihanEmlak/1.0 (Real Estate Management System)',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $lat,
                    'lon' => $lon,
                    'format' => 'json',
                    'addressdetails' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return ResponseService::success([
                    'result' => $data,
                    'coordinates' => ['lat' => $lat, 'lon' => $lon],
                ], 'Reverse geocoding başarıyla tamamlandı');
            } else {
                return ResponseService::serverError('Reverse geocoding başarısız');
            }

        } catch (\Exception $e) {
            Log::error('Reverse geocoding hatası: '.$e->getMessage());

            return ResponseService::serverError('Koordinat arama başarısız.', $e);
        }
    }
}
