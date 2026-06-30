<?php

namespace App\Models\MarketIntelligence;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FeedbackResult extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'feedback_results';

    protected $fillable = [
        'listing_id',
        'snapshot_id',
        'outcome_id',
        'pricing_correct',
        'demand_correct',
        'opportunity_correct',
        'feedback_reason',
    ];

    protected $casts = [
        'listing_id' => 'integer',
        'snapshot_id' => 'integer',
        'outcome_id' => 'integer',
        'pricing_correct' => 'boolean',
        'demand_correct' => 'boolean',
        'opportunity_correct' => 'boolean',
    ];

    public function snapshot()
    {
        return $this->belongsTo(PredictionSnapshot::class, 'snapshot_id');
    }

    public function outcome()
    {
        return $this->belongsTo(ListingOutcome::class, 'outcome_id');
    }
}
