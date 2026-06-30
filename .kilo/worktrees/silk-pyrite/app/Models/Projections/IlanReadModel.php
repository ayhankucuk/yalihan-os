<?php

namespace App\Models\Projections;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * Class IlanReadModel
 *
 * Eloquent model for denormalized listings (Ilan) read model.
 *
 * @package App\Models\Projections
 */
class IlanReadModel extends BaseModel
{
    use HasCountryScope;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ilanlar_read_model';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'ulke_id',
        'ilan_id',
        'son_islenen_sira_numarasi',
        'baslik',
        'aciklama',
        'yayin_durumu',
        'aktiflik_durumu',
        'one_cikan',
        'kapak_resmi',
        'ana_kategori_id',
        'alt_kategori_id',
        'il',
        'ilce',
        'mahalle',
        'lat',
        'lng',
        'fiyat',
        'doviz_birimi',
        'oda_sayisi',
        'banyo_sayisi',
        'brut_alan_m2',
        'net_alan_m2',
        'bina_yasi',
        'bulundugu_kat',
        'sahip_id',
        'sorumlu_danisman_id',
        'display_order',
        'slug',
        'goruntulenme_sayisi',
        'favori_sayisi',
        'iletisim_sayisi',
        'ilan_olusturulma_tarihi',
        'son_guncelleme_tarihi',
    ];
}
