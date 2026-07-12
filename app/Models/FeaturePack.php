<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasCountryScope;

class FeaturePack extends Model
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'feature_packs';

    protected $fillable = [
        'name',
        'slug',
        'display_name',
        'description',
        'icon',
        'color',
        'kategori_ids',
        'yayin_tipi_ids',
        'aktiflik_durumu',
        'display_order',
        'feature_count',
    ];

    protected $casts = [
        'kategori_ids'    => 'array',
        'yayin_tipi_ids'  => 'array',
        'aktiflik_durumu' => 'integer',
        'display_order'   => 'integer',
        'feature_count'   => 'integer',
    ];

    /** Scope: Aktif paketler */
    public function scopeAktif($query)
    {
        return $query->where('aktiflik_durumu', 1);
    }

    /** Scope: Sıralı */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    /** Paketteki özellikler */
    public function items(): HasMany
    {
        return $this->hasMany(FeaturePackItem::class);
    }

    /** Uygulama logları */
    public function logs(): HasMany
    {
        return $this->hasMany(FeaturePackLog::class);
    }

    /** Paketin kaç özellik içerdiğini güncelle */
    public function updateFeatureCount(): void
    {
        $this->updateQuietly(['feature_count' => $this->items()->count()]);
    }
}
