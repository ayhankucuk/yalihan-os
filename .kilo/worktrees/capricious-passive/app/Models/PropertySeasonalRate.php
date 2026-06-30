<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertySeasonalRate extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'property_seasonal_rates';

    protected $fillable = [
        'property_id',
        'start_date',
        'end_date',
        'nightly_rate',
        'weekly_rate',
        'monthly_rate',
        'min_stay_override',
        'season_label',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'start_date'       => 'date',
        'end_date'         => 'date',
        'is_active'  => \App\Enums\AktiflikDurumu::class,
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'property_id');
    }
}
