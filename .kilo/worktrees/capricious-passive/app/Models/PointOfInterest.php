<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;

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
 */
class PointOfInterest extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'point_of_interests';

    protected $fillable = [
        'poi_adi',
        'poi_turu',
        'poi_kategorisi',
        'lat',
        'lng',
        'rating',
        'ek_veri',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'rating' => 'float',
        'ek_veri' => 'array',
        'is_active' => \App\Enums\AktiflikDurumu::class,
    ];
}
