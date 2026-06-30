<?php

namespace App\Models\Projections;

use App\Models\BaseModel;
use App\Models\Ilan;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ️ SAB SEALED
 * Buyer Interest Projection — Alıcı eşleşme ve sorgu yoğunluğu (CQRS).
 */
class BuyerInterestProjection extends BaseModel
{
    use HasCountryScope;
    protected $table = 'buyer_interest_projections';

    protected $fillable = [
        'listing_id',
        'candidate_count',
        'avg_match_score',
        'top_match_score',
        'high_intent_buyer_count',
        'recent_query_count',
    ];

    protected $casts = [
        'candidate_count' => 'integer',
        'avg_match_score' => 'integer',
        'top_match_score' => 'integer',
        'high_intent_buyer_count' => 'integer',
        'recent_query_count' => 'integer',
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'listing_id');
    }
}
