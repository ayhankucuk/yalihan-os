<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ilce extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $table = 'ilceler';

    protected $fillable = [
        'il_id',
        'ilce_adi',
        'ilce_kodu',
        'api_id',
        'lat',
        'lng',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'display_order' => 'integer',
    ];

    /**
     * Bir ilçenin ait olduğu il
     */
    public function il()
    {
        return $this->belongsTo(Il::class, 'il_id');
    }

    /**
     * Bir ilçenin birden çok mahallesi olabilir
     */
    public function mahalleler()
    {
        return $this->hasMany(Mahalle::class, 'ilce_id');
    }

    /**
     * Bir ilçedeki ilanlar
     */
    public function ilanlar()
    {
        return $this->hasMany(Ilan::class, 'ilce_id');
    }
}
