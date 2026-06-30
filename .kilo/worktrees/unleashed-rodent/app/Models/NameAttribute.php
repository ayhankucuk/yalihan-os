<?php

namespace App\Models;

class NameAttribute
{
    /**
     * Laravel 10 uyumlu özellik tanımlama
     *
     * @param  mixed  $model
     * @return string
     */
    public static function resolve($model)
    {
        // Çevirileri kontrol et ve ilk çevirinin adını döndür
        if ($model->relationLoaded('translations') && $model->translations->isNotEmpty()) {
            return $model->translations->first()->name;
        }

        return '';
    }
}
