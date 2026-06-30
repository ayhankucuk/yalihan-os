<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Crypt;

trait EncryptsAttributes
{
    public static function bootEncryptsAttributes()
    {
        static::saving(function ($model) {
            $attrs = property_exists($model, 'encrypted') ? (array) $model->encrypted : [];
            foreach ($attrs as $key) {
                if (array_key_exists($key, $model->attributes) && $model->attributes[$key] !== null) {
                    $plain = $model->attributes[$key];
                    $model->attributes[$key] = Crypt::encryptString((string) $plain);
                }
            }
        });

        static::retrieved(function ($model) {
            $attrs = property_exists($model, 'encrypted') ? (array) $model->encrypted : [];
            foreach ($attrs as $key) {
                if (array_key_exists($key, $model->attributes) && $model->attributes[$key] !== null) {
                    try {
                        $cipher = $model->attributes[$key];
                        $model->attributes[$key] = Crypt::decryptString((string) $cipher);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error("Decryption failed for attribute [{$key}] in model [" . get_class($model) . "]: " . $e->getMessage());
                    }
                }
            }
        });
    }
}
