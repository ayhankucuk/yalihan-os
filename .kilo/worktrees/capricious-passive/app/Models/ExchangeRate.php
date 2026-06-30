<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExchangeRate extends BaseModel
{
    use HasFactory;
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
        'rate' => 'float',
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'effective_at' => 'datetime',
    ];
}
