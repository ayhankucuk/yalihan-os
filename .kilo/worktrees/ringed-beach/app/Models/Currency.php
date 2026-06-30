<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

class Currency extends BaseModel
{
    use HasCountryScope;

    protected $table = 'currencies';

    protected $fillable = [
        'code',
        'symbol',
        'aktiflik_durumu',
        'varsayilan_durumu',
        'display_order',
        'decimal_precision',
    ];

    protected $casts = [
        'aktiflik_durumu'   => \App\Enums\AktiflikDurumu::class,
        'varsayilan_durumu' => 'boolean',
        'display_order'     => 'integer',
        'decimal_precision' => 'integer',
    ];

    /**
     * Scope for active currencies.
     */
    public function scopeActive($query)
    {
        return $query->where('aktiflik_durumu', \App\Enums\AktiflikDurumu::AKTIF);
    }
}
