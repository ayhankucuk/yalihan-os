<?php

namespace App\Modules\Emlak\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FeatureTranslation Model
 *
 * Feature çevirileri — locale bazlı isim ve açıklama.
 * Önceki Deprecated\FeatureTranslation ghost'unun kanonik karşılığı.
 *
 * @property int         $id
 * @property int         $feature_id
 * @property string      $locale
 * @property string      $name
 * @property string|null $description
 */
class FeatureTranslation extends \App\Modules\BaseModule\Models\BaseModel
{
    protected $table = 'feature_translations';

    protected $fillable = [
        'feature_id',
        'locale',
        'name',
        'description',
    ];

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
