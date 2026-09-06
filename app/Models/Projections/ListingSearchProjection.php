<?php

namespace App\Models\Projections;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * ️ SAB SEALED
 * Listing Search Projection — İlan arama ve filtreleme için read model (CQRS).
 *
 * @context7-ignore-file — CQRS projection table uses English column names by design.
 */
class ListingSearchProjection extends BaseModel
{
    use HasCountryScope;

    protected $table = 'listing_search_projection';

    protected $fillable = [
        'listing_id',
        'title',
        'city',
        'district',
        'price',
        'room_count',
        'property_type',
        'features',
        'portfolio_health',
        'seo_score',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'portfolio_health' => 'integer',
        'seo_score' => 'integer',
    ];
}
