<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ️ SAB SEALED
 * Deal Prediction Log — İlan bazlı satış tahmini ve kalite skorlarını tutar.
 *
 * Context7: Naming rules applied.
 */
class DealPredictionLog extends BaseModel
{
    use HasCountryScope;
    protected $table = 'deal_prediction_logs';

    protected $fillable = [
        'listing_id',
        'sale_probability',
        'estimated_days_to_sell',
        'price_accuracy_score',
        'market_heat_score',
        'buyer_interest_score',
        'deal_quality_score',
        'opportunity_score',
        'top_buyer_match_score',
        'reason',
        'metadata',
        'locale',
        'model_version',
    ];

    protected $casts = [
        'sale_probability' => 'integer',
        'estimated_days_to_sell' => 'integer',
        'price_accuracy_score' => 'integer',
        'market_heat_score' => 'integer',
        'buyer_interest_score' => 'integer',
        'deal_quality_score' => 'integer',
        'opportunity_score' => 'integer',
        'top_buyer_match_score' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * İlişkili ilan.
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'listing_id');
    }
}
