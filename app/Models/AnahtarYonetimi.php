<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Anahtar Yönetimi Model
 *
 * Context7: Anahtar teslim sistemi için
 * - Anahtar durumu, teslim tarihi
 * - Anahtar takibi, notlar
 * - İlan ilişkisi
 */
class AnahtarYonetimi extends BaseModel
{
    use HasCountryScope;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'anahtar_yonetimi';

    protected $fillable = [
        'ilan_id',
        'anahtar_statusu',
        'anahtar_durumu',
        'teslim_tarihi',
        'teslim_eden_kisi_id',
        'teslim_alan_kisi_id',
        'anahtar_konumu',
        'anahtar_notlari',
        'anahtar_tipi',
        'anahtar_sayisi',
        'anahtar_ozellikleri',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'teslim_tarihi' => 'datetime',
        'anahtar_ozellikleri' => 'array',
        'anahtar_sayisi' => 'integer',
    ];

    /**
     * İlan ilişkisi
     */
    public function ilan()
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    /**
     * Teslim eden kişi
     */
    public function teslimEden()
    {
        return $this->belongsTo(User::class, 'teslim_eden_kisi_id');
    }

    /**
     * Teslim alan kişi
     */
    public function teslimAlan()
    {
        return $this->belongsTo(User::class, 'teslim_alan_kisi_id');
    }

    /**
     * Context7/Canonical compatibility accessor for anahtar_durumu
     */
    public function getAnahtarDurumuAttribute($value)
    {
        return $this->attributes['anahtar_statusu'] ?? $value ?? 'Beklemede';
    }

    /**
     * Context7/Canonical compatibility mutator for anahtar_durumu
     */
    public function setAnahtarDurumuAttribute($value): void
    {
        $statusValues = ['Beklemede', 'Hazır', 'Teslim Edildi', 'Geri Alındı', 'Kayıp'];
        if (in_array($value, $statusValues, true)) {
            $this->attributes['anahtar_statusu'] = $value;
            if (! isset($this->attributes['anahtar_durumu'])) {
                $this->attributes['anahtar_durumu'] = 'Aktif';
            }
        } else {
            $this->attributes['anahtar_durumu'] = $value;
        }
    }

    /**
     * Check if key can be delivered
     */
    public function canBeDelivered(): bool
    {
        $status = $this->attributes['anahtar_statusu'] ?? $this->anahtar_durumu;

        return in_array($status, ['Hazır', 'Beklemede', 'Geri Alındı'], true);
    }
}
