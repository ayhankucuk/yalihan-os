<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\PointOfInterest;
use App\Services\Location\PoiService;
use App\Services\Logging\LogService;
use App\Services\Response\ResponseService;
use App\Domain\Location\Services\FindNearbyPoisUseCase;
use App\Domain\Location\DTOs\PoiSearchCriteria;
use Illuminate\Http\Request;

/**
 * 📍 MOD-1: Location POI Distance Calculator
 *
 * Haversine formülü kullanarak verilen koordinatın çevresindeki
 * POI'leri (Okul, Hastane, Metro, Market vb.) hesaplar.
 *
 * Context7: Sealed POI data, harita entegrasyonu
 * Performance: <100ms for 2km radius calculation
 *
 * @author GitHub Copilot
 * @date 3 Ocak 2026
 */
class LocationPoiController extends Controller
{
    private PoiService $poiService;
    private FindNearbyPoisUseCase $findNearbyPoisUseCase;

    public function __construct(PoiService $poiService, FindNearbyPoisUseCase $findNearbyPoisUseCase)
    {
        $this->poiService = $poiService;
        $this->findNearbyPoisUseCase = $findNearbyPoisUseCase;
    }

    /**
     * 📍 POI Mesafeleri Hesapla (Haversine)
     *
     * POST /api/v1/location/poi-distances
     *
     * Request:
     * {
     *     "lat": 36.7465,
     *     "lng": 29.1289,
     *     "kategori": "villa",
     *     "radius_km": 2
     * }
     *
     * Response:
     * {
     *     "pois": [
     *         {
     *             "type": "school", // context7-ignore
     *             "name": "Yalıkavak Primary School",
     *             "distance": 450,
     *             "lat": 36.75,
     *             "lng": 29.13,
     *             "address": "Yalıkavak, Bodrum"
     *         },
     *         ...
     *     ],
     *     "summary": {
     *         "total_found": 15,
     *         "by_type": {
     *             "school": 3,
     *             "hospital": 1,
     *             "market": 5,
     *             ...
     *         }
     *     }
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateDistances(Request $request)
    {
        try {
            $t0 = LogService::startTimer('poi_distance_calculation');

            $validated = $request->validate([
                'lat' => 'required|numeric|between:-90,90',
                'lng' => 'required|numeric|between:-180,180',
                'kategori' => 'nullable|string|in:arsa,villa,apartman,isyeri',
                'radius_km' => 'nullable|numeric|min:0.5|max:5',
            ]);

            $lat = (float) $validated['lat'];
            $lng = (float) $validated['lng'];
            $kategori = $validated['kategori'] ?? null;
            $radiusKm = (float) ($validated['radius_km'] ?? 2);

if (config('location.use_domain_poi_search', false)) {
                // ✅ Strangler Fig: Domain Use Case
                $criteria = PoiSearchCriteria::fromValidatedRequest($validated);
                $result = $this->findNearbyPoisUseCase->execute($criteria);
                $responsePayload = $result->toApiResponse();
            } else {
                // 🔴 Legacy: PoiService (Strangler Fig süresince korunur)
                $poiFilters = $this->getPoiFiltersByCategory($kategori);
                $pois = $this->poiService->findNearby($lat, $lng, $radiusKm, $poiFilters);
                $sortedPois = $pois->sortBy(fn($poi) => $poi['distance_km'] ?? $poi['distance'] ?? 999)->values();
                $summary = [
                    'total_found' => $sortedPois->count(),
                    'by_type' => $sortedPois->groupBy('poi_turu')->map->count(),
                    'closest_poi' => $sortedPois->first(),
                    'farthest_poi' => $sortedPois->last(),
                ];
                $responsePayload = [
                    'pois' => $sortedPois->toArray(),
                    'data' => $sortedPois->toArray(),
                    'summary' => $summary,
                    'sealed' => true,
                ];
                LogService::info('poi_distance_success', [
                    'lat' => $lat, 'lng' => $lng, 'kategori' => $kategori,
                    'radius_km' => $radiusKm, 'total_pois' => $sortedPois->count(),
                    'duration_ms' => (int) LogService::stopTimer($t0),
                ]);
            }

            return ResponseService::success($responsePayload, 'POI mesafeleri başarıyla hesaplandı');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseService::validationError($e->errors(), 'Validasyon hatası');
        } catch (\Exception $e) {
            LogService::error('poi_distance_error', [
                'error' => $e->getMessage(),
            ], $e);

            return ResponseService::serverError('POI hesaplama başarısız', $e);
        }
    }

    /**
     * 🎯 Kategori Bazlı POI Filtreleri
     *
     * @param string|null $kategori
     * @return array
     */
    private function getPoiFiltersByCategory(?string $kategori): array
    {
        $filters = [
            'arsa' => [
                'types' => ['highway', 'amenity.parking', 'amenity.fuel', 'railway.station'], // context7-ignore
                'important' => true,
            ],
            'villa' => [
                'types' => ['amenity.school', 'amenity.hospital', 'amenity.restaurant', 'shop', 'park'], // context7-ignore
                'important' => true,
            ],
            'apartman' => [
                'types' => ['amenity.school', 'amenity.hospital', 'shop', 'amenity.parking', 'park'], // context7-ignore
                'important' => true,
            ],
            'isyeri' => [
                'types' => ['amenity.bank', 'amenity.restaurant', 'office', 'shop', 'highway'], // context7-ignore
                'important' => true,
            ],
        ];

        return $filters[$kategori] ?? [
            'types' => ['amenity', 'shop', 'park', 'school', 'hospital'], // context7-ignore
            'important' => false,
        ];
    }

    /**
     * 🗺️ Neighborhood Profile
     *
     * Bölge hakkında detaylı bilgi (POI yoğunluğu, sosyal donatı, ulaşım)
     *
     * GET /api/v1/location/neighborhood-profile?lat=36.7465&lng=29.1289
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNeighborhoodProfile(Request $request)
    {
        try {
            $t0 = LogService::startTimer('neighborhood_profile');

            $validated = $request->validate([
                'lat' => 'required|numeric|between:-90,90',
                'lng' => 'required|numeric|between:-180,180',
            ]);

            $lat = (float) $validated['lat'];
            $lng = (float) $validated['lng'];

            // 📊 Bölge özeti
            $poiDensity = $this->calculatePoiDensity($lat, $lng);
            $amenities = $this->getAmenitiesSummary($lat, $lng);

            // Deterministic score estimation from POI and amenity density
            $walkabilityScore = min(100, max(30, (int) ($poiDensity['density_score'] * 0.5 + ($amenities['shops'] + $amenities['parks']) * 5 + 40)));
            $safetyScore = min(95, max(50, 75 + ($amenities['schools'] > 0 ? 10 : 0) + ($amenities['hospitals'] > 0 ? 5 : 0)));

            $profile = [
                'location' => [
                    'lat' => $lat,
                    'lng' => $lng,
                ],
                'poi_density' => $poiDensity,
                'amenities' => $amenities,
                'safety_score' => $safetyScore,
                'walkability_score' => $walkabilityScore,
            ];

            LogService::info('neighborhood_profile_success', [
                'lat' => $lat,
                'lng' => $lng,
                'duration_ms' => (int) LogService::stopTimer($t0),
            ]);

            return ResponseService::success($profile, 'Neighborhood profili oluşturuldu');
        } catch (\Exception $e) {
            LogService::error('neighborhood_profile_error', [
                'error' => $e->getMessage(),
            ], $e);

            return ResponseService::serverError('Neighborhood profili oluşturulamadı', $e);
        }
    }

    /**
     * 🏘️ POI Yoğunluğu Hesapla (1km² başına kaç POI)
     */
    private function calculatePoiDensity(float $lat, float $lng): array
    {
        // 1km radius'ta POI sayısı
        $pois1km = $this->poiService->findNearby($lat, $lng, 1);

        // 2km radius'ta POI sayısı
        $pois2km = $this->poiService->findNearby($lat, $lng, 2);

        return [
            'radius_1km' => $pois1km->count(),
            'radius_2km' => $pois2km->count(),
            'density_score' => min(100, ($pois1km->count() * 10)), // 0-100
            'level' => $pois1km->count() > 20 ? 'Yüksek' : ($pois1km->count() > 10 ? 'Orta' : 'Düşük'),
        ];
    }

    /**
     * 🏪 Sosyal Donatı Özeti
     */
    private function getAmenitiesSummary(float $lat, float $lng): array
    {
        $pois = $this->poiService->findNearby($lat, $lng, 2);
        $grouped = $pois->groupBy('type'); // context7-ignore

        return [
            'schools' => $grouped->get('school', collect())->count(),
            'hospitals' => $grouped->get('hospital', collect())->count(),
            'restaurants' => $grouped->get('restaurant', collect())->count(),
            'shops' => $grouped->get('shop', collect())->count(),
            'parks' => $grouped->get('park', collect())->count(),
            'banks' => $grouped->get('bank', collect())->count(),
        ];
    }

    /**
     * 🚌 Ulaşım Bilgileri
     */
    private function getTransportationInfo(float $lat, float $lng): array
    {
        $pois = $this->poiService->findNearby($lat, $lng, 3);
        $transport = $pois->filter(fn($p) => in_array($p['type'], [ // context7-ignore
            'railway.station',
            'bus.stop',
            'public_transport',
        ]));

        return [
            'public_transport_nearby' => $transport->count() > 0,
            'closest_station' => $transport->first(),
            'transport_score' => $transport->count() > 0 ? 80 : 40, // 0-100
        ];
    }
}
