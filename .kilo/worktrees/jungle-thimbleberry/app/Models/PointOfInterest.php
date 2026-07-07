<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Support\Str;

/**
 * 📍 Google Maps / OpenStreetMap POI Modeli
 *
 * Context7 Compliance:
 * - poi_adi (name)
 * - poi_turu (type: school, hospital, etc.)
 * - poi_kategorisi (category: egitim, saglik, vb.)
 * - lat/lng (koordinatlar)
 * - ek_veri (json: address, phone, url, rating)
 * - aktiflik_durumu (boolean)
 * - poi_adi_ascii (ASCII-canonical name for deterministic key matching)
 *
 * Fix (2026-07-06):
 * - is_active removed from $fillable — DB column is aktiflik_durumu
 * - poi_adi_ascii added for ASCII-canonical search key (Str::ascii via iconv)
 */
class PointOfInterest extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'point_of_interests';

    protected $fillable = [
        'poi_adi',
        'poi_adi_ascii',
        'poi_turu',
        'poi_kategorisi',
        'lat',
        'lng',
        'rating',
        'ek_veri',
        'aktiflik_durumu',
        'display_order',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'rating' => 'float',
        'ek_veri' => 'array',
        'aktiflik_durumu' => \App\Enums\AktiflikDurumu::class,
    ];

    /**
     * Auto-populate poi_adi_ascii from poi_adi on create/update.
     *
     * Uses Str::ascii (iconv transliteration):
     * Bodrum Anadolu Lisesi → bodrum anadolu lisesi
     * Yalıkavak Marina      → yalikavak marina
     * Gümüşlük Plajı        → gumusluk plaji
     */
    public function setPoiAdiAttribute(string $value): void
    {
        $this->attributes['poi_adi'] = $value;
        $this->attributes['poi_adi_ascii'] = Str::ascii($value);
    }
}
