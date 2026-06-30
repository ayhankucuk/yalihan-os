<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Demirbas extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasCountryScope;

    protected $table = 'demirbaslar';

    protected $fillable = [
        'name',
        'slug',
        'brand',
        'icon',
        'description',
        'kategori_id',
        'ilan_kategori_id',
        'yayin_tipi_id',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => \App\Enums\AktiflikDurumu::class,
        'display_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Deprecated\DemirbasKategori::class, 'kategori_id');
    }

    public function ilanKategori(): BelongsTo
    {
        return $this->belongsTo(IlanKategori::class, 'ilan_kategori_id');
    }

    public function ilanlar(): BelongsToMany
    {
        return $this->belongsToMany(Ilan::class, 'ilan_demirbas', 'demirbas_id', 'ilan_id')
            ->withPivot(['brand', 'model', 'quantity', 'notes', 'display_order', 'is_active'])
            ->withTimestamps();
    }
}
