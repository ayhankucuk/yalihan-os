<?php

namespace App\Repositories;

use App\Enums\IlanDurumu;

use App\Models\Ilan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class CortexHeatmapRepository
{
    /**
     * Get properties with ROI and location data
     *
     * @param array $filters
     * @return Collection
     */
    public function getPropertiesWithROI(array $filters): Collection
    {
        $query = Ilan::query()
            ->whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'yayinda'])
            ->whereNotNull('location_data->latitude')
            ->whereNotNull('location_data->longitude')
            ->whereNotNull('additional_metadata->cortex_ai->cortex_score');

        // Apply filters
        if (isset($filters['il_id'])) {
            $query->where('il_id', $filters['il_id']);
        }

        if (isset($filters['min_roi'])) {
            $query->where('additional_metadata->cortex_ai->roi_data->roi_percentage', '>=', $filters['min_roi']);
        }

        if (isset($filters['category_id'])) {
            $query->where('ana_kategori_id', $filters['category_id']);
        }

        if (isset($filters['min_price'])) {
            $query->where('fiyat', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('fiyat', '<=', $filters['max_price']);
        }

        return $query->select([
                'id',
                'baslik',
                'fiyat',
                'il_id',
                'ilce_id',
                'location_data',
                'additional_metadata',
            ])->get();
    }

    /**
     * Get properties in specific grid cell
     *
     * @param float $minLat
     * @param float $maxLat
     * @param float $minLng
     * @param float $maxLng
     * @return Collection
     */
    public function getPropertiesInCellBounds(float $minLat, float $maxLat, float $minLng, float $maxLng): Collection
    {
        return Ilan::query()
            ->whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'yayinda'])
            ->whereBetween('location_data->latitude', [$minLat, $maxLat])
            ->whereBetween('location_data->longitude', [$minLng, $maxLng])
            ->with(['il', 'ilce', 'anaKategori'])
            ->get();
    }

    /**
     * Get total property count with coordinates
     *
     * @return int
     */
    public function countWithCoordinates(): int
    {
        return DB::table('ilanlar')
            ->whereNotNull('location_data->latitude')
            ->whereNotNull('location_data->longitude')
            ->count();
    }

    /**
     * Get total property count with ROI
     *
     * @return int
     */
    public function countWithROI(): int
    {
        return DB::table('ilanlar')
            ->whereNotNull('additional_metadata->cortex_ai->cortex_score')
            ->count();
    }

    /**
     * Get property count ready for heatmap
     *
     * @return int
     */
    public function countReadyForHeatmap(): int
    {
        return DB::table('ilanlar')
            ->whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'active', 'yayinda'])
            ->whereNotNull('location_data->latitude')
            ->whereNotNull('location_data->longitude')
            ->whereNotNull('additional_metadata->cortex_ai->cortex_score')
            ->count();
    }
}
