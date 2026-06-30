<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class CanonicalBooleanCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?bool
    {
        if ($value === null) {
            return null;
        }

        return (bool) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        return $value ? 1 : 0;
    }
}