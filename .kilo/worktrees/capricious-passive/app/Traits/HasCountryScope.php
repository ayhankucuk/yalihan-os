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
}
