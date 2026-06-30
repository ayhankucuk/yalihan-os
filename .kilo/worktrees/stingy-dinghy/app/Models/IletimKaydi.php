<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İletim Kaydı Model
 *
 * [YALIHAN_COMMUNICATION_0206]
 * WhatsApp/Email/Telegram iletim kayıtları
 */
class IletimKaydi extends BaseModel
{
    use HasCountryScope;

    protected $table = 'iletim_kayitlari';

    protected $fillable = [
        'ilan_id',
        'alici_tipi',
        'alici_kimlik',
        'iletim_kanali',
        'icerik_sablonu',
        'imzali_url',
        'basarili_mi',
        'iletim_mührü',
        'hata_detayi',
        'metadata',
    ];

    protected $casts = [
        'basarili_mi' => 'boolean',
        'iletim_mührü' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * İlan ilişkisi
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class);
    }

    /**
     * Başarılı iletimler scope
     */
    public function scopeBasarili($query)
    {
        return $query->where('basarili_mi', true);
    }

    /**
     * Belirli kanal için scope
     */
    public function scopeKanal($query, string $kanal)
    {
        return $query->where('iletim_kanali', $kanal);
    }
}
