<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyGrowthProjection extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'property_growth_projections';

    protected $fillable = [
        'property_id',
        'yearly_growth_rate',
        'projection_years',
        'projection_type',
        'is_active',
    ];

    protected $casts = [
        'yearly_growth_rate' => 'float',
        'projection_years'   => 'integer',
        'is_active'    => \App\Enums\AktiflikDurumu::class,
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'property_id');
    }
}
