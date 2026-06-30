<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends BaseModel
{
    use HasCountryScope;

    protected $table = 'languages';

    protected $fillable = [
        'code',
        'name',
        'aktiflik_durumu',
        'varsayilan_durumu',
        'is_rtl',
        'display_order',
    ];

    protected $casts = [
        'aktiflik_durumu'   => \App\Enums\AktiflikDurumu::class,
        'varsayilan_durumu' => 'boolean',
        'is_rtl'            => 'boolean',
        'display_order'     => 'integer',
    ];

    /**
     * Scope for active languages.
     */
    public function scopeActive($query)
    {
        return $query->where('aktiflik_durumu', \App\Enums\AktiflikDurumu::AKTIF);
    }
}
