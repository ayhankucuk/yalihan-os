<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for Publication Type - Category specific feature assignments.
 * Context7 Compliant.
 */
class YayinTipiPivotAtama extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'yayin_tipi_pivot_atamalari';

    protected $fillable = [
        'yayin_tipi_id',
        'alt_kategori_id',
        'feature_id',
        'zorunlu_mu',
        'gosterim_durumu',
        'display_order',
    ];

    protected $casts = [
        'zorunlu_mu' => 'boolean',
        'gosterim_durumu' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Related Publication Type Template
     */
    public function yayinTipi(): BelongsTo
    {
        return $this->belongsTo(YayinTipiSablonu::class, 'yayin_tipi_id');
    }

    /**
     * Related Category
     */
    public function altKategori(): BelongsTo
    {
        return $this->belongsTo(IlanKategori::class, 'alt_kategori_id');
    }

    /**
     * Related Feature
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }
}
