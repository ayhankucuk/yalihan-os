<?php

namespace App\Models;

use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * FeatureValue Model
 *
 * Polimorfik özellik değerleri — Ilan, Kisi gibi modellerin
 * HasFeatures trait'i aracılığıyla tuttuğu değerler.
 * Önceki Deprecated\FeatureValue ghost'unun kanonik karşılığı.
 *
 * @property int         $id
 * @property string      $valuable_type  Polimorfik tip
 * @property int         $valuable_id
 * @property int         $feature_id
 * @property string|null $value          Ham değer
 * @property string|null $value_type     string|integer|boolean|json
 */
class FeatureValue extends BaseModel
{
    use HasCountryScope;

    protected $table = 'feature_values';

    protected $fillable = [
        'valuable_type',
        'valuable_id',
        'feature_id',
        'value',
        'value_type',
    ];

    // -------------------------------------------------------------------------
    // İlişkiler
    // -------------------------------------------------------------------------

    /** Polimorfik sahip (Ilan, Kisi, vb.) */
    public function valuable(): MorphTo
    {
        return $this->morphTo();
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Feature::class);
    }

    // -------------------------------------------------------------------------
    // Accessor
    // -------------------------------------------------------------------------

    /**
     * Tipine göre dönüştürülmüş değer.
     * HasFeatures::getFeatureValue() tarafından çağrılır.
     */
    public function getTypedValueAttribute(): mixed
    {
        return match ($this->value_type) {
            'integer' => (int) $this->value,
            'boolean' => (bool) $this->value,
            'json'    => json_decode($this->value, true),
            default   => $this->value,
        };
    }

    // -------------------------------------------------------------------------
    // Statik Yardımcılar — HasFeatures trait API'si
    // -------------------------------------------------------------------------

    /**
     * Bir model için tüm feature değerlerini slug→value map olarak döndür.
     */
    public static function getForModel(object $model): array
    {
        return static::where('valuable_type', get_class($model))
            ->where('valuable_id', $model->id)
            ->with('feature:id,slug')
            ->get()
            ->mapWithKeys(fn ($fv) => [$fv->feature->slug => $fv->typed_value])
            ->toArray();
    }

    /**
     * Tek feature değeri kaydet veya güncelle.
     */
    public static function setForModel(object $model, object $feature, mixed $value): static
    {
        $valueType = match (true) {
            is_bool($value)    => 'boolean',
            is_int($value)     => 'integer',
            is_array($value)   => 'json',
            default            => 'string',
        };

        $storedValue = is_array($value) ? json_encode($value) : (string) $value;

        return static::updateOrCreate(
            [
                'valuable_type' => get_class($model),
                'valuable_id'   => $model->id,
                'feature_id'    => $feature->id,
            ],
            [
                'value'      => $storedValue,
                'value_type' => $valueType,
            ]
        );
    }

    /**
     * Toplu feature değerleri kaydet (slug=>value array).
     */
    public static function bulkSetForModel(object $model, array $values): void
    {
        $slugs    = array_keys($values);
        $features = \App\Models\Feature::whereIn('slug', $slugs)->get()->keyBy('slug');

        foreach ($values as $slug => $value) {
            $feature = $features->get($slug);
            if ($feature) {
                static::setForModel($model, $feature, $value);
            }
        }
    }
}
