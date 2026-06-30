<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class YazlikDetail extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $table = 'yazlik_details';

    protected $fillable = [
        'ilan_id',
        'min_konaklama',
        'max_misafir',
        'temizlik_ucreti',
        'havuz',
        'havuz_turu',
        'havuz_boyut',
        'havuz_derinlik',
        'havuz_boyut_en',
        'havuz_boyut_boy',
        'gunluk_fiyat',
        'haftalik_fiyat',
        'aylik_fiyat',
        'sezonluk_fiyat',
        'sezon_baslangic',
        'sezon_bitis',
        'elektrik_dahil',
        'su_dahil',
        'internet_dahil',
        'carsaf_dahil',
        'havlu_dahil',
        'klima_var',
        'ozel_notlar',
        // Context7: musteri_notlari → kisi_notlari (migration: 2025_11_11_103355)
        'indirim_notlari',
        'indirimli_fiyat',
        'anahtar_kimde',
        'anahtar_notlari',
        'sahip_ozel_notlari',
        'sahip_iletisim_tercihi',
        'eids_onayli',
        'eids_onay_tarihi',
        'eids_belge_no',
        'oda_sayisi',
        'banyo_sayisi',
        'yatak_sayisi',
        'yatak_turleri',
        'restoran_mesafe',
        'market_mesafe',
        'deniz_mesafe',
        'merkez_mesafe',
        'bahce_var',
        'tv_var',
        'barbeku_var',
        'sezlong_var',
        'bahce_masasi_var',
        'manzara',
        'ozel_isaretler',
        'ev_tipi',
        'ev_konsepti',
    ];

    protected $casts = [
        'havuz' => 'boolean',
        'elektrik_dahil' => 'boolean',
        'su_dahil' => 'boolean',
        'internet_dahil' => 'boolean',
        'carsaf_dahil' => 'boolean',
        'havlu_dahil' => 'boolean',
        'klima_var' => 'boolean',
        'bahce_var' => 'boolean',
        'tv_var' => 'boolean',
        'barbeku_var' => 'boolean',
        'sezlong_var' => 'boolean',
        'bahce_masasi_var' => 'boolean',
        'eids_onayli' => 'boolean',
        'temizlik_ucreti' => 'decimal:2',
        'gunluk_fiyat' => 'decimal:2',
        'haftalik_fiyat' => 'decimal:2',
        'aylik_fiyat' => 'decimal:2',
        'sezonluk_fiyat' => 'decimal:2',
        'indirimli_fiyat' => 'decimal:2',
        'sezon_baslangic' => 'date',
        'sezon_bitis' => 'date',
        'eids_onay_tarihi' => 'date',
        'yatak_turleri' => 'array',
        'ozel_isaretler' => 'array',
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }
}
