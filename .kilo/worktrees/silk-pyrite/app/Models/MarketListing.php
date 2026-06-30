<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * Market Listing Model
 *
 * Third-party market listing data (Sahibinden, Hürriyet Emlak, etc.)
 * Context7 Compliant: ✅
 *
 * @property int $id
 * @property int|null $ilan_id Harici ilan ID
 * @property string|null $baslik
 * @property float|null $fiyat
 * @property int|null $para_birimi
 * @property int|null $danisman_id
 * @property int|null $kategori_id
 * @property int|null $il_id
 * @property int $gecen_gun_sayisi
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class MarketListing extends BaseModel
{
    use HasCountryScope;

    protected $table = 'proj_listings';

    protected $fillable = [
        'ilan_id',
        'baslik',
        'yayin_durumu',
        'fiyat',
        'para_birimi',
        'danisman_id',
        'kategori_id',
        'il_id',
        'gecen_gun_sayisi',
    ];

    protected $casts = [
        'fiyat' => 'float',
        'para_birimi' => 'integer',
        'danisman_id' => 'integer',
        'kategori_id' => 'integer',
        'il_id' => 'integer',
        'gecen_gun_sayisi' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
