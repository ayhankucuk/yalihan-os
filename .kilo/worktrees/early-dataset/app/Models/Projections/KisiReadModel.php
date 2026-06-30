<?php

namespace App\Models\Projections;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * Class KisiReadModel
 *
 * Eloquent model for denormalized persons (Kisi) read model.
 *
 * @package App\Models\Projections
 */
class KisiReadModel extends BaseModel
{
    use HasCountryScope;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kisiler_read_model';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'ulke_id',
        'uuid',
        'ad_soyad',
        'telefon_numarasi',
        'eposta_adresi',
        'musteri_segmenti',
        'iletisim_tercihleri',
        'kimlik_dogrulama_durumu',
        'aktiflik_durumu',
        'son_islenen_sira_numarasi',
        'olusturulma_zamani',
        'degistirilme_zamani',
        'display_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'iletisim_tercihleri' => 'array',
        'kimlik_dogrulama_durumu' => 'boolean',
        'aktiflik_durumu' => 'boolean',
        'display_order' => 'integer',
        'son_islenen_sira_numarasi' => 'integer',
    ];
}
