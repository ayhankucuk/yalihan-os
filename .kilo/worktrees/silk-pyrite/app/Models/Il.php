<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class Il extends BaseModel
{
    use SoftDeletes;
    use HasCountryScope;

    protected $table = 'iller';

    // Plaka kodu manuel set edilecek (auto-increment KAPALI)
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'api_id',
        'il_adi',
        'plaka_kodu',
        'telefon_kodu',
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
     * İle ait ilçeleri getiren ilişki
     */
    public function ilceler()
    {
        return $this->hasMany(Ilce::class, 'il_id');
    }

    /**
     * İlin bağlı olduğu ülkeyi getiren ilişki
     */
    public function ulke()
    {
        return $this->belongsTo(Ulke::class, 'ulke_id');
    }

    public function mahalleler()
    {
        return $this->hasManyThrough(Mahalle::class, Ilce::class, 'il_id', 'ilce_id');
    }

    public function ilanlar()
    {
        return $this->hasMany(\App\Models\Ilan::class, 'il_id');
    }
}
