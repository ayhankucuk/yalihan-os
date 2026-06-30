<?php

namespace App\Models\Projections;

use App\Models\BaseModel;
use App\Models\Ilan;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ️ SAB SEALED
 * Listing Velocity Projection — İlan aktivite ve hız sinyalleri (CQRS).
 */
class ListingVelocityProjection extends BaseModel
{
    use HasCountryScope;
    protected $table = 'listing_velocity_projections';

    protected $fillable = [
        'listing_id',
        'view_count',
        'favorite_count',
        'inquiry_count',
        'share_count',
        'last_activity_at',
        'activity_score',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'view_count' => 'integer',
        'favorite_count' => 'integer',
        'inquiry_count' => 'integer',
        'share_count' => 'integer',
        'activity_score' => 'integer',
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'listing_id');
    }
}
