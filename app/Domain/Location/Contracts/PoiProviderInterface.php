<?php

namespace App\Domain\Location\Contracts;

use App\Domain\Location\DTOs\PoiSearchCriteria;
use Illuminate\Support\Collection;

/**
 * 📍 POI Provider — Driven Port (Hexagonal Architecture)
 *
 * Verilen koordinatın çevresindeki POI'leri mesafe bilgisiyle birlikte döndürür.
 * Her adapter bu portu implemente eder (DatabaseHaversinePoiAdapter, HttpGooglePlacesAdapter, vs).
 *
 * @see DatabaseHaversinePoiAdapter
 */
interface PoiProviderInterface
{
    /**
     * Koordinat çevresindeki POI'leri bul.
     *
     * @param PoiSearchCriteria $criteria Arama kriteri (koordinat, yarıçap, filtreler)
     * @return Collection<int, array{
     *     id: int,
     *     poi_adi: string,
     *     poi_turu: string,
     *     poi_kategorisi: string|null,
     *     type: string,
     *     name: string,
     *     distance_km: float,
     *     distance: int,
     *     lat: float,
     *     lng: float,
     *     rating: float|null,
     *     ek_veri: array|null,
     *     address: string|null
     * }>
     */
    public function findNearby(PoiSearchCriteria $criteria): Collection;

    /**
     * Adapter'ın hangi veri kaynağını kullandığını döndürür.
     * Logging ve debugging için.
     */
    public function getProviderName(): string;
}
