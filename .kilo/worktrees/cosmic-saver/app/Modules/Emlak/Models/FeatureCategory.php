<?php

namespace App\Modules\Emlak\Models;

use App\Models\IlanKategori;
use App\Modules\Emlak\Models\FeatureCategoryTranslation;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $slug
 * @property string|null $name
 * @property string|null $description
 * @property string|null $applies_to
 * @property string|null $icon
 * @property int $display_order
 * @property bool $aktiflik_durumu
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $seo_keywords
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Emlak\Models\Feature> $features
 * @property-read int|null $features_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, FeatureCategoryTranslation> $translations
 * @property-read int|null $translations_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureCategory whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class FeatureCategory extends Model
{
    /**
     * İlişkilendirilmiş tablo adı
     *
     * @var string
     */
    protected $table = 'feature_categories';

    /**
     * Toplu atanabilir alanlar
     *
     * @var array
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'applies_to',
        'icon',
        'display_order',
        'aktiflik_durumu',
        'meta_title',
        'meta_description',
        'seo_keywords',
    ];

    /**
     * Otomatik olarak yüklenecek ilişkiler
     */
    protected $with = [];

    /**
     * Cast edilecek özellikler
     *
     * @var array
     */
    protected $casts = [
        'aktiflik_durumu' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Bu kategoriye ait özellikler
     */
    public function features()
    {
        return $this->hasMany(Feature::class, 'feature_category_id');
    }

    /**
     * Kategori adını çevirilerden al
     */
    public function getName(): string
    {
        // Eğer name sütunu doluysa onu kullan
        if (! empty($this->name)) {
            return $this->name;
        }

        // Yoksa çevirilerden al
        if ($this->translations->isNotEmpty()) {
            $turkishTranslation = $this->translations->where('locale', 'tr')->first();
            if ($turkishTranslation) {
                return $turkishTranslation->getAttribute('name');
            }

            return $this->translations->first()->getAttribute('name');
        }

        return $this->slug ?? 'İsimsiz Kategori';
    }

    /**
     * Kategori adını set et ve kaydet
     */
    public function setNameFromTranslations(): void
    {
        if (empty($this->name) && $this->translations->isNotEmpty()) {
            $turkishTranslation = $this->translations->where('locale', 'tr')->first();
            if ($turkishTranslation) {
                $this->name = $turkishTranslation->getAttribute('name');
            } else {
                $this->name = $this->translations->first()->getAttribute('name');
            }
            $this->save();
        }
    }

    /**
     * Kategorinin çevirileri
     */
    public function translations()
    {
        return $this->hasMany(FeatureCategoryTranslation::class, 'feature_category_id');
    }

    /**
     * Kategori adını döndüren yardımcı metot
     * Bu metot, Laravel 10 uyumlu bir şekilde tanımlandı
     *
     * @return string
     */
    public function getTranslatedName()
    {
        // Çevirileri kontrol et ve ilk çevirinin adını döndür
        if ($this->relationLoaded('translations') && $this->translations->isNotEmpty()) {
            return $this->translations->first()->name;
        }

        return '';
    }

    /**
     * Bu özellik kategorisi ile ilişkili ilan kategorilerini getirir.
     */
    public function ilanKategorileri()
    {
        return $this->belongsToMany(IlanKategori::class, 'ilan_kategori_feature_kategori', 'feature_kategori_id', 'category_id');
    }

    /**
     * Scope: Only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('aktiflik_durumu', true);
    }

    /**
     * Get only active features (Context7: aktiflik_durumu canonical)
     */
    public function activeFeatures()
    {
        return $this->features()
            ->where('aktiflik_durumu', true)
            ->orderBy('display_order');
    }
}
