<?php

namespace App\Casts;

use App\Enums\YatirimciProfili;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Safe Enum Cast for YatirimciProfili
 *
 * PHP 8.4 Compatibility: Handles null/invalid values gracefully
 * Prevents "Cannot instantiate enum" errors
 */
class NullableYatirimciProfiliCast implements CastsAttributes
{
    /**
     * Cast the given value to YatirimciProfili enum or null
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?YatirimciProfili
    {
        return YatirimciProfili::tryFromDatabase($value);
    }

    /**
     * Prepare the given value for storage
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof YatirimciProfili) {
            return $value->value;
        }

        // Try to convert string to enum
        $enum = YatirimciProfili::tryFromDatabase($value);
        
        return $enum?->value;
    }
}
