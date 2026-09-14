<?php

namespace App\Domain\Location\Services;

use App\Domain\Location\Adapters\DatabaseHaversinePoiAdapter;
use App\Domain\Location\DTOs\PoiSearchCriteria;
use App\Services\Logging\LogService;
use Illuminate\Support\Collection;

/**
 * 📍 Find Nearby POIs — Application Use Case
 *
 * Location bounded context'in merkezi use case'i.
 * Controller'lar bunu kullanır — PoiService veya adapter'lara doğrudan erişmez.
 *
 * Feature toggle: config('location.use_domain_poi_search')
 * Kapalıysa legacy PoiService döner (Strangler Fig — eski kod çalışmaya devam eder).
 * Açıkken: yeni adapter + use case devreye girer.
 *
 * @see DatabaseHaversinePoiAdapter
 * @see PoiSearchCriteria
 */
class FindNearbyPoisUseCase
{
    public function __construct(
        private readonly ?DatabaseHaversinePoiAdapter $adapter = null,
    ) {}

    /**
     * Koordinat çevresindeki POI'leri bul + summary üret.
     *
     * @param PoiSearchCriteria $criteria
     * @return PoiSearchResult
     */
    public function execute(PoiSearchCriteria $criteria): PoiSearchResult
    {
        $t0 = LogService::startTimer('poi_distance_calculation');

        $adapter = $this->resolveAdapter();
        $pois = $adapter->findNearby($criteria);

        // Mesafeye göre sırala (null-safe)
        $sorted = $pois->sortBy(
            fn(array $poi) => $poi['distance_km'] ?? 9999
        )->values();

        $summary = $this->buildSummary($sorted);

        LogService::info('poi_distance_success', [
            'lat' => $criteria->lat,
            'lng' => $criteria->lng,
            'kategori' => $criteria->kategori,
            'radius_km' => $criteria->radiusKm,
            'total_pois' => $sorted->count(),
            'provider' => $adapter->getProviderName(),
            'duration_ms' => (int) LogService::stopTimer($t0),
        ]);

        return new PoiSearchResult(
            pois: $sorted->toArray(),
            summary: $summary,
        );
    }

    /**
     * Feature toggle'a göre adapter seç.
     * Kapalı → legacy (Strangler Fig geçiş sürecinde controller'da zaten ayrılık yapılıyor)
     */
    private function resolveAdapter(): DatabaseHaversinePoiAdapter
    {
        if (!config('location.use_domain_poi_search', false)) {
            throw new \RuntimeException(
                'LocationPoiController should NOT call FindNearbyPoisUseCase when ' .
                'config("location.use_domain_poi_search") is false. ' .
                'Feature flag must be enabled before delegating to domain use case. ' .
                'Legacy code path is handled directly in the controller for now.'
            );
        }

        return $this->adapter ?? new DatabaseHaversinePoiAdapter();
    }

    private function buildSummary(Collection $sortedPois): array
    {
        $total = $sortedPois->count();

        if ($total === 0) {
            return [
                'total_found' => 0,
                'by_type' => [],
                'closest_poi' => null,
                'farthest_poi' => null,
            ];
        }

        $byType = $sortedPois->groupBy('poi_turu')->map(fn($group) => $group->count())->toArray();

        return [
            'total_found' => $total,
            'by_type' => $byType,
            'closest_poi' => $sortedPois->first(),
            'farthest_poi' => $sortedPois->last(),
        ];
    }
}
