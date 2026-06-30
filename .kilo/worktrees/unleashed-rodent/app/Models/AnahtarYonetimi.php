<?php

namespace App\Models;

use App\Models\BaseModel;
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
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $table = 'anahtar_yonetimi';

    protected $fillable = [
        'ilan_id',
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
}
