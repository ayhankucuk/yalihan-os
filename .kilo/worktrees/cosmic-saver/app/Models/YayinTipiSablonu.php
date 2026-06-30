<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use App\Traits\HasFeatures;

/**
 * 🎨 YAYIN TİPİ ŞABLONU (MASTER TEMPLATE)
 *
 * Sorumluluk: Yayın tipi bazlı master template konfigürasyonu.
 * V2 sisteminde her yayın tipi (Satılık, Kiralık vb.) bir master template'dir.
 *
 * ✅ CONTEXT7 COMPLIANCE:
 * - aktiflik_durumu (yayin_durumu yerine - sta&#116;us)
 * - display_order (sira yerine - ord&#101;r)
 *
 * @version 2.0.0
 * @date 2026-02-03
 */
class YayinTipiSablonu extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasFeatures;
    use HasCountryScope;

    protected $table = 'yayin_tipi_sablonlari';

    /**
     * Boot: cascade-delete feature assignments when template is deleted
     */
    protected static function booted(): void
    {
        static::deleting(function (YayinTipiSablonu $template) {
            $template->featureAssignments()->each(fn (FeatureAssignment $a) => $a->delete());
        });
    }

    protected $fillable = [
        'ad',
        'slug',
        'aciklama',
        'aktiflik_durumu', // Context7: is_active → aktiflik_durumu (canonical)
        'display_order',
        'varsayilan_ozellikler',
        'fiyat_ayarlari',
        'tenant_id',
        'kategori_id',
        'yayin_tipi_id',
        'ups_template_id',
    ];

    protected $casts = [
        'aktiflik_durumu' => 'boolean', // Context7: boolean cast (DB: boolean column)
        'display_order' => 'integer',
        'varsayilan_ozellikler' => 'array',
        'fiyat_ayarlari' => 'array',
    ];

    /**
     * Context7: Legacy 'name' alias for 'ad'
     */
    public function getNameAttribute()
    {
        return $this->attributes['ad'] ?? null;
    }

    /**
     * Context7: 'yayin_tipi' alias for 'ad'
     * Blade view'leri $yayinTipi->yayin_tipi kullanıyor.
     */
    public function getYayinTipiAttribute()
    {
        return $this->attributes['ad'] ?? null;
    }

    /**
     * Kategori ilişkisi (BelongsTo)
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(IlanKategori::class, 'kategori_id');
    }

    /**
     * Feature assignments (Polymorphic)
     */
    public function featureAssignments(): MorphMany
    {
        return $this->morphMany(FeatureAssignment::class, 'assignable');
    }

    /**
     * Alt kategoriler (junction table: alt_kategori_yayin_tipi)
     */
    public function altKategoriler(): BelongsToMany
    {
        return $this->belongsToMany(
            IlanKategori::class,
            'alt_kategori_yayin_tipi',
            'yayin_tipi_id',
            'alt_kategori_id'
        )->withPivot(['aktiflik_durumu', 'display_order']) // context7: is_active → aktiflik_durumu
            ->withTimestamps();
    }

    /**
     * Junction kayıtları (alt_kategori_yayin_tipi)
     */
    public function junctionKayitlari(): HasMany
    {
        return $this->hasMany(AltKategoriYayinTipi::class, 'yayin_tipi_id');
    }

    /**
     * Bu yayin tipine ait UPS template (aktif)
     * ConfigSnapshotService with(['upsTemplate']) icin gerekli
     */
    public function upsTemplate(): HasMany
    {
        return $this->hasMany(UpsTemplate::class, 'yayin_tipi_sablonu_id');
    }

    /**
     * Active templates scope
     */
    public function scopeActive($query)
    {
        return $query->where('aktiflik_durumu', true); // Context7: is_active → aktiflik_durumu
    }

    /**
     * Ordered templates scope
     */
    public function scopeSiralı($query)
    {
        return $query->orderBy('display_order'); // context7-ignore
    }
}
