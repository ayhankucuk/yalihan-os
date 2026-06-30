<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

class FxRate extends BaseModel
{
    use HasCountryScope;

    protected $table = 'fx_rates';

    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'is_active',
        'effective_at',
    ];

    protected $casts = [
        'rate'            => 'float',
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'effective_at'    => 'datetime',
    ];
}
