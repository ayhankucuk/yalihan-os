<?php

namespace App\Models;

use App\Traits\HasCountryScope;

/**
 * DemirbasKategori Model
 *
 * Demirbas kategorileri.
 * Önceki Deprecated\DemirbasKategori ghost'unun kanonik karşılığı.
 *
 * @property int         $id
 * @property string      $ad
 * @property string|null $slug
 * @property string|null $aciklama
 * @property bool        $aktiflik_durumu
 * @property int         $display_order
 */
class DemirbasKategori extends BaseModel
{
    use HasCountryScope;

    protected $table = 'demirbas_kategorileri';

    protected $fillable = [
        'ad',
        'slug',
        'aciklama',
        'aktiflik_durumu',
        'display_order',
    ];

    protected $casts = [
        'aktiflik_durumu' => 'boolean',
        'display_order'   => 'integer',
    ];

    // -------------------------------------------------------------------------
    // İlişkiler
    // -------------------------------------------------------------------------

    public function demirbaslar(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Demirbas::class, 'kategori_id');
    }

    // -------------------------------------------------------------------------
    // Scope'lar
    // -------------------------------------------------------------------------

    public function scopeAktif($query)
    {
        return $query->where('aktiflik_durumu', true);
    }

    public function scopeOrdered($query) // context7-ignore
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}
