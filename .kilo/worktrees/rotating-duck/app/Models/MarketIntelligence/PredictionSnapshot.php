<?php

namespace App\Models\MarketIntelligence;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PredictionSnapshot extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'prediction_snapshots';

    protected $fillable = [
        'listing_id',
        'pricing_position',
        'pricing_score',
        'demand_score',
        'demand_label',
        'confidence_score',
        'confidence_label',
        'opportunity_action',
        'opportunity_score',
        'priority_score',
        'priority_label',
        'current_price',
        'benchmark_price',
        'snapshot_at',
    ];

    protected $casts = [
        'listing_id' => 'integer',
        'pricing_score' => 'integer',
        'demand_score' => 'integer',
        'confidence_score' => 'integer',
        'opportunity_score' => 'integer',
        'priority_score' => 'integer',
        'current_price' => 'decimal:2',
        'benchmark_price' => 'decimal:2',
        'snapshot_at' => 'datetime',
    ];

    public function outcome()
    {
        return $this->hasOneThrough(
            ListingOutcome::class,
            FeedbackResult::class,
            'snapshot_id',
            'id',
            'id',
            'outcome_id',
        );
    }

    public function feedbackResults()
    {
        return $this->hasMany(FeedbackResult::class, 'snapshot_id');
    }
}
