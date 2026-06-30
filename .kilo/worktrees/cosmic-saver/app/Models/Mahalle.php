<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mahalle extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $table = 'mahalleler';

    protected $fillable = [
        'mahalle_adi',
        'ilce_id',
        'posta_kodu',
        'mahalle_kodu',
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

    public function ilce()
    {
        return $this->belongsTo(Ilce::class, 'ilce_id');
    }

    /**
     * Bir mahalledeki ilanlar
     */
    public function ilanlar()
    {
        return $this->hasMany(Ilan::class, 'mahalle_id');
    }
}
