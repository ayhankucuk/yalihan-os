<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Yazlık Fiyatlandırma Modeli
 *
 * Sezonluk fiyatlandırma sistemini yönetir
 */
class YazlikFiyatlandirma extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'yazlik_fiyatlandirma';

    protected $fillable = [
        'ilan_id',
        'sezon_tipi',
        'baslangic_tarihi',
        'bitis_tarihi',
        'gunluk_fiyat',
        'haftalik_fiyat',
        'aylik_fiyat',
        'minimum_konaklama',
        'maksimum_konaklama',
        'ozel_gunler',
        'is_active',
    ];

    protected $casts = [
        'baslangic_tarihi' => 'date',
        'bitis_tarihi' => 'date',
        'gunluk_fiyat' => 'decimal:2',
        'haftalik_fiyat' => 'decimal:2',
        'aylik_fiyat' => 'decimal:2',
        'minimum_konaklama' => 'integer',
        'maksimum_konaklama' => 'integer',
        'ozel_gunler' => 'array',
        'is_active' => \App\Enums\AktiflikDurumu::class,
    ];

    /**
     * İlan ilişkisi
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class);
    }

    /**
     * Sezon tipi seçenekleri
     */
    public static function getSezonTipleri(): array
    {
        return [
            'yaz' => 'Yaz Sezonu',
            'ara_sezon' => 'Ara Sezon',
            'kis' => 'Kış Sezonu',
        ];
    }

    /**
     * Aktif fiyatlandırma scope'u
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', \App\Enums\AktiflikDurumu::AKTIF->value);
    }

    /**
     * Belirli tarih aralığında fiyat getir
     */
    public function scopeTarihAraliginda($query, $baslangic, $bitis)
    {
        return $query->where(function ($q) use ($baslangic, $bitis) {
            $q->whereBetween('baslangic_tarihi', [$baslangic, $bitis])
                ->orWhereBetween('bitis_tarihi', [$baslangic, $bitis])
                ->orWhere(function ($q2) use ($baslangic, $bitis) {
                    $q2->where('baslangic_tarihi', '<=', $baslangic)
                        ->where('bitis_tarihi', '>=', $bitis);
                });
        });
    }

    /**
     * Fiyat hesaplama (gün sayısına göre)
     */
    public function calculatePrice(int $days): float
    {
        if ($days >= 30 && $this->aylik_fiyat) {
            $months = ceil($days / 30);

            return $this->aylik_fiyat * $months;
        }

        if ($days >= 7 && $this->haftalik_fiyat) {
            $weeks = ceil($days / 7);

            return $this->haftalik_fiyat * $weeks;
        }

        return $this->gunluk_fiyat * $days;
    }
}
