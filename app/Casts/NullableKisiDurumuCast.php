<?php

namespace App\Casts;

use App\Enums\KisiDurumu;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Safe Enum Cast for KisiDurumu (crm_surec_asamasi)
 *
 * PHP 8.4 Compatibility: Handles null/invalid values gracefully
 * Prevents "is not a valid backing value" ValueError exceptions
 */
class NullableKisiDurumuCast implements CastsAttributes
{
    /**
     * Cast the given value to KisiDurumu enum or null
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?KisiDurumu
    {
        return KisiDurumu::tryFromDatabase($value);
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

        if ($value instanceof KisiDurumu) {
            return $value->value;
        }

        $enum = KisiDurumu::tryFromDatabase($value);

        return $enum ? $enum->value : (is_string($value) ? $value : null);
    }
}
