<?php

namespace App\Services;

use App\Repositories\WikimapiaSearchRepository;
use App\Services\TurkiyeAPIService;

class WikimapiaSearchService
{
    protected TurkiyeAPIService $turkiyeAPI;
    protected WikimapiaSearchRepository $repo;

    public function __construct(TurkiyeAPIService $turkiyeAPI, WikimapiaSearchRepository $repo)
    {
        $this->turkiyeAPI = $turkiyeAPI;
        $this->repo = $repo;
    }

    /**
     * Koordinatlardan mahalle ve ilçe bilgisini bulur, TurkiyeAPI ile detayları getirir
     */
    public function getLocationFromCoordinates(float $lat, float $lon): array
    {
        // Yakındaki mahalleleri repository ile bul (5km yarıçap)
        $nearbyMahalleler = $this->repo->findNearbyMahalleler($lat, $lon, 5);

        if (!empty($nearbyMahalleler)) {
            $mahalle = $nearbyMahalleler[0];
            $ilce = $this->repo->findIlceWithIlById($mahalle->ilce_id);
            $allLocations = $this->turkiyeAPI->getAllLocations($mahalle->ilce_id);

            return [
                'success' => true,
                'data' => [
                    'mahalle' => [
                        'id' => $mahalle->id,
                        'name' => $mahalle->mahalle_adi,
                        'distance' => round($mahalle->distance * 1000),
                    ],
                    'ilce' => $ilce ? [
                        'id' => $ilce->id,
                        'name' => $ilce->ilce_adi,
                    ] : null,
                    'il' => $ilce && $ilce->il ? [
                        'id' => $ilce->il->id,
                        'name' => $ilce->il->il_adi,
                    ] : null,
                    'all_locations' => $allLocations,
                ],
                'source' => 'turkiyeapi+local_db',
            ];
        }

        return [
            'success' => false,
            'message' => 'Yakında lokasyon bulunamadı',
        ];
    }
}
