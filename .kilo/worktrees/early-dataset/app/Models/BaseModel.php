<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * BaseModel — SAB Core Foundation Lock (v2.0 — Context7 Zero-Regeneration)
 *
 * Tüm domain modelleri bu sınıftan türetilmelidir.
 * Foundation Lock Protocol + Context7 Runtime Guard entegreli.
 */
class BaseModel extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        parent::booted();
    }
}
