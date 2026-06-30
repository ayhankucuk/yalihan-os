<?php

namespace App\Models;

use App\Traits\HasFeatures;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\IncrementsStateVersion;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class IlanKategori extends BaseModel
{
    use HasFeatures;
    use SoftDeletes;
    use IncrementsStateVersion;
    use HasFactory;
    use HasCountryScope;
    use \App\Traits\HasActiveScope;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ilan_kategorileri';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'parent_id',
        'seviye',
        'aktiflik_durumu',
        'display_order',
        'slug',
        'icon',
        'aciklama',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'aktiflik_durumu' => \App\Enums\AktiflikDurumu::class,
        'seviye' => 'integer',
        'display_order' => 'integer',
    ];

    /**
     * Üst kategoriyi döndüren ilişki (self-referencing)
     */
    public function parent()
    {
        return $this->belongsTo(IlanKategori::class, 'parent_id');
    }

    /**
     * Alt kategorileri döndüren ilişki (self-referencing)
     */
    public function children()
    {
        return $this->hasMany(IlanKategori::class, 'parent_id');
    }

    /**
     * Eager-loadable alt kategoriler ilişkisi
     */
    public function altKategoriler(): HasMany
    {
        return $this->hasMany(IlanKategori::class, 'parent_id')
            ->where('aktiflik_durumu', \App\Enums\AktiflikDurumu::AKTIF)
            ->orderBy('display_order') // context7-ignore
            ->orderBy('id'); // context7-ignore
    }

    /**
     * Kategoriye bağlı yayın tipleri (V2: alt_kategori_yayin_tipi junction üzerinden).
     */
    public function yayinTipleri(): BelongsToMany
    {
        return $this->belongsToMany(
            YayinTipiSablonu::class,
            'alt_kategori_yayin_tipi',
            'alt_kategori_id',
            'yayin_tipi_id'
        )->withPivot('aktiflik_durumu', 'display_order');
    }

    public function anaKategoriIlanlar()
    {
        return $this->hasMany(Ilan::class, 'ana_kategori_id');
    }

    public function altKategoriIlanlar()
    {
        return $this->hasMany(Ilan::class, 'alt_kategori_id');
    }

    public function ilanlar()
    {
        $ilanTable = (new Ilan)->getTable();
        $hasKategori = Schema::hasColumn($ilanTable, 'kategori_id');
        $hasAna = Schema::hasColumn($ilanTable, 'ana_kategori_id');
        $hasAlt = Schema::hasColumn($ilanTable, 'alt_kategori_id');
        $hasYayin = Schema::hasColumn($ilanTable, 'yayin_tipi_id');

        if ($hasKategori) {
            return $this->hasMany(Ilan::class, 'kategori_id');
        }

        if ((int) ($this->seviye ?? -1) === 2 && $hasYayin) {
            return $this->hasMany(Ilan::class, 'yayin_tipi_id');
        }
        if ($hasAlt) {
            return $this->hasMany(Ilan::class, 'alt_kategori_id');
        }
        if ($hasAna) {
            return $this->hasMany(Ilan::class, 'ana_kategori_id');
        }

        return $this->hasMany(Ilan::class, 'kategori_id')->where('id', '<', 0);
    }


    public function scopeAnaKategoriler($query)
    {
        return $query->where('seviye', 0)->orderBy('id'); // context7-ignore
    }

    public function scopeAltKategoriler($query)
    {
        return $query->where('seviye', 1)->orderBy('id'); // context7-ignore
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('id'); // context7-ignore
    }

    /**
     * Bu kategorinin kendine ait özellik ataması (custom template) var mı?
     */
    public function hasCustomTemplate(): bool
    {
        return $this->featureAssignments()->exists();
    }

    /**
     * Seviye açıklamaları (0=Ana, 1=Alt, 2=Yayın Tipi)
     */
    public static function getSeviyeAciklamalari(): array
    {
        return [
            0 => 'Ana Kategori',
            1 => 'Alt Kategori',
            2 => 'Yayın Tipi',
        ];
    }

    /**
     * Icon alanını emoji olarak döndürür.
     */
    public function getIconEmojiAttribute(): string
    {
        $map = [
            'home' => '🏠', 'building' => '🏢', 'map' => '🗺️', 'sun' => '☀️',
            'hotel' => '🏨', 'rocket' => '🚀', 'apartment' => '🏬', 'villa' => '🏡',
            'house' => '🏘️', 'duplex' => '🏗️', 'office' => '🏛️', 'shop' => '🏪',
            'factory' => '🏭', 'warehouse' => '📦', 'land' => '🌍', 'tree' => '🌳',
            'beach' => '🏖️', 'farm' => '🌾', 'parking' => '🅿️', 'gas' => '⛽',
            'store' => '🛒', 'restaurant' => '🍽️', 'cafe' => '☕', 'hospital' => '🏥',
            'school' => '🏫', 'mosque' => '🕌', 'church' => '⛪', 'park' => '🌲',
            'pool' => '🏊', 'gym' => '💪', 'marina' => '⚓', 'airport' => '✈️',
        ];

        return $map[$this->icon] ?? '📁';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($kategori) {
            if (empty($kategori->slug)) {
                $baseSlug = \Illuminate\Support\Str::slug($kategori->name);
                $slug = $baseSlug;
                $counter = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $kategori->slug = $slug;
            }
        });
    }
}
