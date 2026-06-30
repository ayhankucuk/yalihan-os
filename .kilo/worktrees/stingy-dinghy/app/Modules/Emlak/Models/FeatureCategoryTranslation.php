<?php

namespace App\Modules\Emlak\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FeatureCategoryTranslation Model
 *
 * FeatureCategory çevirileri — locale bazlı isim ve açıklama.
 * Önceki Deprecated\FeatureCategoryTranslation ghost'unun kanonik karşılığı.
 *
 * @property int         $id
 * @property int         $feature_category_id
 * @property string      $locale
 * @property string      $name
 * @property string|null $description
 */
class FeatureCategoryTranslation extends \App\Modules\BaseModule\Models\BaseModel
{
    protected $table = 'feature_category_translations';

    protected $fillable = [
        'feature_category_id',
        'locale',
        'name',
        'description',
    ];

    public function featureCategory(): BelongsTo
    {
        return $this->belongsTo(FeatureCategory::class);
    }
}
