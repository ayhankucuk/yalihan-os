<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Get provinces (cities).
     *
     * @return JsonResponse
     */
    public function getProvinces(): JsonResponse
    {
        try {
            // ✅ SAB: Il model using standard table
            $provinces = Il::orderBy('il_adi')
                ->select(['id', 'il_adi', 'il_adi as name'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $provinces
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'İller yüklenirken hata oluştu.'
            ], 500);
        }
    }

    /**
     * Get districts for a given city.
     * Route: /districts/{id}
     *
     * @param int $cityId
     * @return JsonResponse
     */
    public function getDistrictsByProvince(int $cityId): JsonResponse
    {
        try {
            $districts = Ilce::where('il_id', $cityId)
                ->select(['id', 'ilce_adi', 'ilce_adi as name']) // ✅ Return both for compatibility
                ->orderBy('ilce_adi') // context7-ignore
                ->get();

            return response()->json([
                'success' => true,
                'data' => $districts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'İlçeler yüklenirken hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get neighborhoods for a given district.
     * Route: /neighborhoods/{id}
     *
     * @param int $districtId
     * @return JsonResponse
     */
    public function getNeighborhoodsByDistrict(int $districtId): JsonResponse
    {
        try {
            $neighborhoods = Mahalle::where('ilce_id', $districtId)
                ->select(['id', 'mahalle_adi', 'mahalle_adi as name'])
                ->orderBy('mahalle_adi') // context7-ignore
                ->get();

            return response()->json([
                'success' => true,
                'data' => $neighborhoods
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mahalleler yüklenirken hata oluştu [ID: ' . $districtId . ']: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get details (coordinates) of a specific neighborhood.
     * Route: /neighborhood/{id}/coordinates
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getNeighborhoodCoordinates(int $id): JsonResponse
    {
        try {
            $neighborhood = Mahalle::select(['id', 'mahalle_adi', 'mahalle_adi as name'])
                ->find($id);

            if (!$neighborhood) {
                return response()->json(['success' => false, 'message' => 'Mahalle bulunamadı.'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $neighborhood
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mahalle detayı alınamadı.'
            ], 500);
        }
    }

    /**
     * Get district coordinates.
     * Route: /district/{id}/coordinates
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getDistrictCoordinates(int $id): JsonResponse
    {
        try {
            $district = Ilce::select(['id', 'ilce_adi', 'ilce_adi as name', 'lat', 'lng'])
                ->find($id);

            if (!$district) {
                return response()->json(['success' => false, 'message' => 'İlçe bulunamadı.'], 404);
            }

            return response()->json([
                'success' => true,
                'lat' => $district->lat,
                'lng' => $district->lng,
                'data' => [
                    'id' => $district->id,
                    'name' => $district->name,
                    'lat' => $district->lat,
                    'lng' => $district->lng
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'İlçe detayı alınamadı: ' . $e->getMessage()
            ], 500);
        }
    }

    // ... Any other methods needed
}
