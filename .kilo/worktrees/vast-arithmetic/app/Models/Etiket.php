<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Etiket extends BaseModel
{
    use SoftDeletes;
    use HasCountryScope;
    use \App\Traits\HasActiveScope;

    protected $table = 'etiketler';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'display_order' => 'integer',
        'is_badge' => 'boolean',
    ];

    public function ilanlar(): BelongsToMany
    {
        return $this->belongsToMany(Ilan::class, 'ilan_etiketler')
            ->withPivot(['display_order', 'one_cikan'])
            ->withTimestamps();
    }
}
