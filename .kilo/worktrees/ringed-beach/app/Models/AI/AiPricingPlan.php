<?php

namespace App\Models\AI;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiPricingPlan extends BaseModel
{
    use HasCountryScope;

    protected $table = 'ai_pricing_plans';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'is_active'
    ];

    protected $casts = [
        'is_active' => \App\Enums\AktiflikDurumu::class,
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(AiFeaturePrice::class, 'plan_id');
    }
}
