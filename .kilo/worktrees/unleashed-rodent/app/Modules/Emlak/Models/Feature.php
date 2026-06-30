<?php

namespace App\Modules\Emlak\Models;

use App\Models\Ilan;
use App\Modules\Emlak\Models\FeatureTranslation;
use App\Modules\BaseModule\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $feature_category_id
 * @property string $slug
 * @property string|null $name
 * @property string|null $description
 * @property string|null $type
 * @property string|null $unit
 * @property bool $is_required
 * @property bool $is_filterable
 * @property bool $is_searchable
 * @property int $display_order
 * @property bool $aktiflik_durumu
 * @property string $lifecycle
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Modules\Emlak\Models\FeatureCategory $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ilan> $ilanlar
 * @property-read int|null $ilanlar_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, FeatureTranslation> $translations
 * @property-read int|null $translations_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature whereIsFilterable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature whereShowOnCard($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feature withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Feature extends BaseModel
{
    use SoftDeletes;

    /**
     * İlişkilendirilmiş tablo adı
     *
     * @var string
     */
    protected $table = 'features';

    /**
     * Toplu atanabilir alanlar
     *
     * @var array
     */
    protected $fillable = [
        'feature_category_id',
        'slug',
        'name',
        'description',
        'type',
        'options',
        'unit',
        'applies_to',
        'is_required',
        'is_filterable',
        'is_searchable',
        'display_order',
        'aktiflik_durumu',
        'lifecycle',
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
        'is_required' => 'boolean',
        'is_filterable' => 'boolean',
        'is_searchable' => 'boolean',
        'aktiflik_durumu' => 'boolean',
        'options' => 'json',
        'display_order' => 'integer',
    ];

    /**
     * Özelliğin kategorisi
     */
    public function category()
    {
        return $this->belongsTo(FeatureCategory::class, 'feature_category_id');
    }

    /**
     * Özelliğin çevirileri
     */
    public function translations()
    {
        return $this->hasMany(FeatureTranslation::class, 'feature_id');
    }

    /**
     * Özellik adını çevirilerden al
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

        return $this->slug ?? 'İsimsiz Özellik';
    }

    /**
     * Özellik adını set et ve kaydet
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

    // Name accessor'u kaldırıldı

    /**
     * Bu özelliğe sahip ilanlar
     */
    public function ilanlar()
    {
        return $this->belongsToMany(Ilan::class, 'ilan_feature', 'feature_id', 'ilan_id')
            ->withTimestamps();
    }

    /**
     * Aktif özellikleri filtrele
     */
    public function scopeActive($query)
    {
        return $query->where('aktiflik_durumu', true);
    }
}
