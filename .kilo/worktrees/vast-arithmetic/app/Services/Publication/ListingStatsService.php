<?php

namespace App\Services\Publication;

use App\Enums\IlanDurumu;

use App\Models\Ilan;
use App\Models\Il;

/**
 * Service for computing listing statistics and aggregations.
 */
class ListingStatsService
{
    /**
     * Calculate stats for portfolio view
     */
    public function getPortfolioStats(): array
    {
        $totalValue = Ilan::byYayinDurumu(IlanDurumu::YAYINDA->value)->whereNotNull('fiyat')->sum('fiyat');
        
        return [
            'total_properties' => Ilan::count(),
            'active_properties' => Ilan::byYayinDurumu(IlanDurumu::YAYINDA->value)->count(),
            'total_value' => $totalValue ? ($totalValue / 1000000) : 0,
            'locations' => Il::distinct()->count('id'),
        ];
    }
}
