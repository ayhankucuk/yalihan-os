<?php

namespace App\Models\AI;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

class AIOpportunityLog extends BaseModel
{
    use HasCountryScope;

    protected $table = 'ai_opportunity_logs';

    protected $fillable = [
        'listing_id',
        'opportunity_score',
        'opportunity_reason',
        'ek_bilgiler',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'ek_bilgiler' => 'array',
        'is_active' => 'integer',
        'display_order' => 'integer',
    ];
}
