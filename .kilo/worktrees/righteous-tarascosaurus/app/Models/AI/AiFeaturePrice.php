<?php

namespace App\Models\AI;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFeaturePrice extends BaseModel
{
    use HasCountryScope;

    protected $table = 'ai_feature_prices';

    protected $fillable = [
        'plan_id',
        'feature_slug',
        'base_cost_credits',
        'is_dynamic',
        'multiplier'
    ];

    protected $casts = [
        'base_cost_credits' => 'integer',
        'is_dynamic' => 'boolean',
        'multiplier' => 'decimal:2'
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(AiPricingPlan::class, 'plan_id');
    }
}
