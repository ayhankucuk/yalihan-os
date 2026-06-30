<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CategoryFeatureWhitelist
 *
 * Kategori bazlı izin verilen feature_category slug listesi.
 * Table: category_feature_whitelist
 * Context7: aktiflik_durumu (aktiflik_durumu yerine)
 */
class CategoryFeatureWhitelist extends BaseModel
{
    use HasCountryScope;

    protected $table = 'category_feature_whitelist';

    protected $fillable = [
        'kategori_id',
        'feature_category_slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => \App\Enums\AktiflikDurumu::class,
    ];

    /**
     * Sadece aktif kayıtlar
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', \App\Enums\AktiflikDurumu::AKTIF);
    }

    /**
     * Kategoriye ait giriş
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(IlanKategori::class, 'kategori_id');
    }
}
