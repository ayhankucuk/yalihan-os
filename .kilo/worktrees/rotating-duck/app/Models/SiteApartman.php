<?php

namespace App\Models;

use App\Enums\IlanDurumu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * Site/Apartman Model
 *
 * Context7: Site/Apartman yönetimi için
 * - Site adı, toplam daire sayısı
 * - Portal entegrasyonu
 * - İlan ilişkisi
 */
class SiteApartman extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'site_apartmanlar';

    protected $fillable = [
        'name',
        'tip',
        'toplam_daire_sayisi',
        'kat_sayisi',
        'asansor_sayisi',
        'adres',
        'il_id',
        'ilce_id',
        'mahalle_id',
        // ✅ SAB: latitude YASAK
        // ✅ SAB: longitude YASAK
        'yonetici_adi',
        'yonetici_telefon',
        'yonetici_email',
        'kapici_telefon',
        'sosyal_tesisler',
        'guvenlik_sistemi',
        'aidat_tutari',
        'aidat_para_birimi',
        'aidat_periyodu',
        'yapim_yili',
        'yapi_tarzi',
        'isitma_sistemi',
        'notlar',
    ];

    protected $casts = [
        'lat' => 'float',   // ✅ SAB
        'lng' => 'float',   // ✅ SAB
        'toplam_daire_sayisi' => 'integer',
        'kat_sayisi' => 'integer',
        'asansor_sayisi' => 'integer',
        'aidat_tutari' => 'decimal:2',
    ];

    /**
     * İl ilişkisi
     */
    public function il()
    {
        return $this->belongsTo(Il::class, 'il_id');
    }

    /**
     * İlçe ilişkisi
     */
    public function ilce()
    {
        return $this->belongsTo(Ilce::class, 'ilce_id');
    }

    /**
     * Mahalle ilişkisi
     */
    public function mahalle()
    {
        return $this->belongsTo(Mahalle::class, 'mahalle_id');
    }

    /**
     * İlanlar ilişkisi
     */
    public function ilanlar()
    {
        return $this->hasMany(Ilan::class, 'site_id');
    }

    /**
     * Oluşturan kullanıcı
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Güncelleyen kullanıcı
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope: Aktif siteler
     */
    public function scopeActive($query)
    {
        return $query; // No aktiflik_durumu column in schema
    }

    /**
     * Canonical aktiflik_durumu accessor.
     */
    public function getAktiflikDurumuAttribute()
    {
        return IlanDurumu::YAYINDA->value; // Virtual default; schema has no aktiflik_durumu column
    }

    /**
     * Canonical aktiflik_durumu mutator (maps legacy aktiflik_durumu column when present).
     */
    public function setAktiflikDurumuAttribute($value): void
    {
        // No-op: schema has no aktiflik_durumu column
    }

    /**
     * Scope: İl bazında filtreleme
     */
    public function scopeByIl($query, $ilId)
    {
        return $query->where('il_id', $ilId);
    }

    /**
     * Scope: İlçe bazında filtreleme
     */
    public function scopeByIlce($query, $ilceId)
    {
        return $query->where('ilce_id', $ilceId);
    }

    /**
     * Tam adres getir
     */
    public function getFullAddressAttribute()
    {
        $parts = [];

        if ($this->adres) {
            $parts[] = $this->adres;
        }

        if ($this->mahalle) {
            $parts[] = $this->mahalle->mahalle_adi;
        }

        if ($this->ilce) {
            $parts[] = $this->ilce->ilce_adi;
        }

        if ($this->il) {
            $parts[] = $this->il->il_adi;
        }

        return implode(', ', $parts);
    }

    /**
     * Site özelliklerini getir
     */
    public function getSiteFeaturesAttribute()
    {
        return $this->site_ozellikleri ?? [];
    }

    /**
     * İlan sayısını getir
     */
    public function getIlanCountAttribute()
    {
        return $this->ilanlar()->count();
    }

    /**
     * Aktif ilan sayısını getir
     */
    public function getActiveIlanCountAttribute()
    {
        return $this->ilanlar()->where('yayin_durumu', IlanDurumu::YAYINDA->value)->count();
    }
}
