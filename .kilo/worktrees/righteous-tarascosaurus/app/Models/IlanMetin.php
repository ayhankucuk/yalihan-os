<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IlanMetin extends BaseModel
{
    use HasCountryScope;

    protected $table = 'ilan_metinleri';

    protected $fillable = [
        'ilan_id',
        'baslik',
        'aciklama',
        'ton',
        'taslak_durumu',
        'is_active',
        'yapay_zeka_durumu',
        'kaynak_veriler'
    ];

    protected $casts = [
        'kaynak_veriler' => 'array',
        'taslak_durumu' => 'boolean',
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'yapay_zeka_durumu' => 'boolean'
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    /**
     * CONTEXT7: Yayında olan metinler
     */
    public function scopeAktif($query)
    {
        return $query->where('is_active', \App\Enums\AktiflikDurumu::AKTIF);
    }

    /**
     * CONTEXT7: Taslak metinler
     */
    public function scopeTaslak($query)
    {
        return $query->where('taslak_durumu', true);
    }
}
