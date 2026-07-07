<?php

namespace App\Traits;

use App\Scopes\CountryScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Trait HasCountryScope
 *
 * ✅ Çoklu ülke operasyonunda veri izolasyonu sağlar.
 * Auto-sets ulke_id on creation from authenticated user.
 */
trait HasCountryScope
{
    public static function bootHasCountryScope()
    {
        static::addGlobalScope(new CountryScope);

        // Auto-set ulke_id on creation if not already set
        static::creating(function ($model) {
            if (Auth::check() && Schema::hasColumn($model->getTable(), 'ulke_id')) {
                if (empty($model->ulke_id)) {
                    $model->ulke_id = Auth::user()->ulke_id;
                }
            }
        });
    }

    /**
     * CountryScope global scope'u devre dışı bırakır.
     * Test/sistem operasyonlarında ülke izolasyonu olmaksızın sorgu yapmak için kullanılır.
     */
    public static function withoutCountryScope(): \Illuminate\Database\Eloquent\Builder
    {
        return static::query()->withoutGlobalScope(CountryScope::class);
    }

    /**
     * CountryScope ile find işlemi yapar (auth ulke_id filter devrede).
     */
    public static function findWithCountryScope(int $id): ?\App\Models\BaseModel
    {
        return static::find($id);
    }

    /**
     * CountryScope olmadan find işlemi yapar.
     * Test DB veya cross-country operasyonlar için.
     */
    public static function findWithoutCountryScope(int $id): ?\App\Models\BaseModel
    {
        return static::withoutCountryScope()->find($id);
    }

    /**
     * CountryScope olmadan findOrFail işlemi yapar.
     */
    public static function findOrFailWithoutCountryScope(int $id): \App\Models\BaseModel
    {
        return static::withoutCountryScope()->findOrFail($id);
    }
}
