<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * VIP Tercih Matrisi Model
 *
 * [YALIHAN_COMMUNICATION_0206]
 * VIP yatırımcı tercih ve filtre ayarları
 */
class VipTercihMatrisi extends BaseModel
{
    use HasCountryScope;
    protected $table = 'vip_tercih_matrisi';

    protected $fillable = [
        'vip_kimlik',
        'vip_adi',
        'tercih_lokasyonlar',
        'tercih_kategoriler',
        'min_fiyat',
        'max_fiyat',
        'para_birimi',
        'tercih_kanal',
        'telefon',
        'email',
        'is_active',
        'notlar',
    ];

    protected $casts = [
        'tercih_lokasyonlar' => 'array',
        'tercih_kategoriler' => 'array',
        'min_fiyat' => 'decimal:2',
        'max_fiyat' => 'decimal:2',
        'is_active' => \App\Enums\AktiflikDurumu::class,
    ];

    /**
     * Aktif VIP'ler scope
     */
    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * WhatsApp tercihi olanlar scope
     */
    public function scopeWhatsApp($query)
    {
        return $query->where('tercih_kanal', 'whatsapp')
            ->whereNotNull('telefon');
    }

    /**
     * Belirli kanal tercihi olanlar
     */
    public function scopeKanal($query, string $kanal)
    {
        return $query->where('tercih_kanal', $kanal);
    }
}
