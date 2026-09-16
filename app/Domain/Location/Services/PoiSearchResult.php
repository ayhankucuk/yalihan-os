<?php

namespace App\Domain\Location\Services;

use Illuminate\Support\Collection;

/**
 * 📍 POI Arama Sonucu DTO
 *
 * API contract: { pois, data, summary, sealed }
 * 'data' = frontend uyumluluk için pois'lerin mirror'u
 * 'sealed' = sözleşmenin kilitli olduğunu belirten flag
 */
final class PoiSearchResult
{
    /**
     * @param array<int, array{
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
     * }> $pois
     * @param array{total_found: int, by_type: array<string, int>, closest_poi: array|null, farthest_poi: array|null} $summary
     */
    public function __construct(
        public readonly array $pois,
        public readonly array $summary,
    ) {}

    /**
     * Legacy API contract formatına dönüştür.
     *
     * @return array{pois: array, data: array, summary: array, sealed: true}
     */
    public function toApiResponse(): array
    {
        return [
            'pois' => $this->pois,
            'data' => $this->pois,           // Frontend uyumluluk mirror
            'summary' => $this->summary,
            'sealed' => true,                // Contract kilitli — Strangler Fig sonrası değişmez
        ];
    }
}
