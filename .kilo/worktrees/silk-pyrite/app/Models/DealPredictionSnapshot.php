<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ️ SAB SEALED
 * Deal Prediction Snapshot — Tarihsel deal skoru trendlerini tutar.
 */
class DealPredictionSnapshot extends BaseModel
{
    use HasCountryScope;
    protected $table = 'deal_prediction_snapshots';

    protected $fillable = [
        'listing_id',
        'snapshot_date',
        'sale_probability',
        'estimated_days_to_sell',
        'deal_quality_score',
        'market_heat_score',
        'metadata',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'sale_probability' => 'integer',
        'estimated_days_to_sell' => 'integer',
        'deal_quality_score' => 'integer',
        'market_heat_score' => 'integer',
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
