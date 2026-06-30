<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İlan Özellik Model
 *
 * Context7 standartlarına uygun ilan özellik yönetimi
 */
class IlanOzellik extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'ilan_ozellikleri';

    protected $fillable = [
        'ilan_id',
        'ozellik_id',
        'deger',
        'aciklama',
    ];

    /**
     * Scope: Aktif özellikler
     */
    public function scopeAktif($query)
    {
        return $query->where('is_active', \App\Enums\AktiflikDurumu::AKTIF);
    }

    /**
     * Scope: İlan ID'ye göre filtrele
     */
    public function scopeIlanId($query, $ilanId)
    {
        return $query->where('ilan_id', $ilanId);
    }

    /**
     * Scope: Özellik ID'ye göre filtrele
     */
    public function scopeOzellikId($query, $ozellikId)
    {
        return $query->where('ozellik_id', $ozellikId);
    }
}
