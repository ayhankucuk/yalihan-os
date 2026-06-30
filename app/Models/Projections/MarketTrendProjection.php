<?php

namespace App\Models\Projections;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * ️ SAB SEALED
 * Market Trend Projection — Lokasyon ve özellik bazlı pazar trendleri (CQRS).
 */
class MarketTrendProjection extends BaseModel
{
    use HasCountryScope;
    protected $table = 'market_trend_projections';

    protected $fillable = [
        'city',
        'district',
        'property_type',
        'avg_price',
        'median_price',
        'price_change_7d',
        'price_change_30d',
        'demand_index',
        'listing_count',
    ];

    protected $casts = [
        'avg_price' => 'decimal:2',
        'median_price' => 'decimal:2',
        'price_change_7d' => 'decimal:2',
        'price_change_30d' => 'decimal:2',
        'demand_index' => 'integer',
        'listing_count' => 'integer',
    ];
}
