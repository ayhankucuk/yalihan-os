<?php

namespace App\Models\V2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ilan Model - V2 API Wrapper for V1 Schema
 *
 * Context7: Accessors ile V1 schema'yı Context7 uyumlu hale getir
 */
class Ilan extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $table = 'ilanlar';

    protected static function newFactory()
    {
        return \Database\Factories\V2\IlanFactory::new();
    }

    protected $fillable = [
        'baslik',
        'slug',
        'aciklama',
        'fiyat',
        'user_id',
        'danisman_id',
        'ana_kategori_id',
        'alt_kategori_id',
        'yayin_tipi_id',
        'il_id',
        'ilce_id',
        'mahalle_id',
        'adres',
        'oda_sayisi',
        'salon_sayisi',
        'banyo_sayisi',
        'kat',
        'toplam_kat',
        'brut_m2',
        'net_m2',
        'bina_yasi',
        'isitma',
        'aidat',
        'esyali',
        'ilan_no',
        'goruntulenme',
        'lat',
        'lng',
        'yayin_durumu',
    ];

    protected $casts = [
        'fiyat' => 'decimal:2',
        'brut_m2' => 'decimal:2',
        'net_m2' => 'decimal:2',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'esyali' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function danisman()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Accessors for Context7 API compatibility
    public function getYayinDurumuAttribute()
    {
        return $this->attributes['yayin_durumu'] ?? 'Taslak';
    }

    public function getBirimFiyatAttribute()
    {
        return $this->fiyat;
    }

    public function getAlanM2Attribute()
    {
        return $this->brut_m2;
    }

    public function getDansismanIdAttribute()
    {
        return $this->user_id;
    }

    public function getIlAttribute()
    {
        return $this->il_id;
    }

    public function getIlceAttribute()
    {
        return $this->ilce_id;
    }

    public function getMahalleAttribute()
    {
        return $this->mahalle_id;
    }

    public function getOneCikanAttribute()
    {
        return false;
    }

    // Missing Relationships needed for API
    public function il()
    {
        return $this->belongsTo(\App\Models\Il::class, 'il_id');
    }

    public function ilce()
    {
        return $this->belongsTo(\App\Models\Ilce::class, 'ilce_id');
    }

    public function mahalle()
    {
        return $this->belongsTo(\App\Models\Mahalle::class, 'mahalle_id');
    }

    public function anaKategori()
    {
        return $this->belongsTo(\App\Models\IlanKategori::class, 'ana_kategori_id');
    }

    public function fotograflar()
    {
        return $this->hasMany(\App\Models\IlanFotografi::class, 'ilan_id');
    }
}
