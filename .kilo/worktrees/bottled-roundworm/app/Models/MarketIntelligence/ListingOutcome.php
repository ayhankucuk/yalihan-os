<?php

namespace App\Models\MarketIntelligence;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ListingOutcome extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'listing_outcomes';

    protected $fillable = [
        'listing_id',
        'outcome_type',
        'days_to_close',
        'final_price',
        'price_changes_count',
        'lead_count',
        'closed_at',
    ];

    protected $casts = [
        'listing_id' => 'integer',
        'days_to_close' => 'integer',
        'final_price' => 'decimal:2',
        'price_changes_count' => 'integer',
        'lead_count' => 'integer',
        'closed_at' => 'datetime',
    ];

    public function feedbackResults()
    {
        return $this->hasMany(FeedbackResult::class, 'outcome_id');
    }
}
