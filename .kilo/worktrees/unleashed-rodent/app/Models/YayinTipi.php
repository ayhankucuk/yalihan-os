<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Global Yayın Tipi Model (SSOT)
 *
 * Context7 Compliance: %100
 */
class YayinTipi extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'yayin_tipleri';

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => \App\Enums\AktiflikDurumu::class,
    ];

    /**
     * Kategori bazlı yayın tipi eşleşmeleri (pivotlar)
     * @deprecated Use YayinTipiSablonu instead
     */
    /*
    public function kategoriBaglantilari(): HasMany
    {
        return $this->hasMany(YayinTipiSablonu::class, 'yayin_tipi_id');
    }
    */

    /**
     * Bu tipe bağlı şablonlar
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Deprecated\IlanTemplate::class, 'yayin_tipi_id');
    }
}
