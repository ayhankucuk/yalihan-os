<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alt Kategori - Yayın Tipi Pivot Tablo Modeli
 */
class AltKategoriYayinTipi extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'alt_kategori_yayin_tipi';

    protected $fillable = [
        'alt_kategori_id',
        'yayin_tipi_id',
        'aktiflik_durumu', // context7: is_active → aktiflik_durumu
        'display_order',
    ];

    protected $casts = [
        'aktiflik_durumu' => \App\Enums\AktiflikDurumu::class, // context7: is_active → aktiflik_durumu
        'display_order' => 'integer',
    ];

    public $timestamps = true;

    /**
     * Alt kategori ilişkisi
     */
    public function altKategori(): BelongsTo
    {
        return $this->belongsTo(IlanKategori::class, 'alt_kategori_id');
    }

    /**
     * Yayın tipi ilişkisi
     */
    public function yayinTipi(): BelongsTo
    {
        return $this->belongsTo(YayinTipiSablonu::class, 'yayin_tipi_id');
    }

    /**
     * Aktif pivot kayıtları
     */
    public function scopeActive($query)
    {
        return $query->where('aktiflik_durumu', \App\Enums\AktiflikDurumu::AKTIF->value); // context7: is_active → aktiflik_durumu
    }
}
