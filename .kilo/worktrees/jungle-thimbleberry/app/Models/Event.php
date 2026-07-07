<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DEPRECATED — Use YazlikRezervasyon instead.
 *
 * Event and YazlikRezervasyon both point to yazlik_rezervasyonlar table.
 * Event is a minimal stub with no scopes or helper methods.
 * YazlikRezervasyon is the full SSOT implementation.
 *
 * SSOT consolidation (Sprint 5.2 — 2026-07-06):
 * yazlik_rezervasyonlar table → YazlikRezervasyon is the sole authoritative model.
 *
 * @deprecated 2026-07-06 — SSOT: use App\Models\YazlikRezervasyon
 * @see YazlikRezervasyon
 */
class Event extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'yazlik_rezervasyonlar';

    /**
     * SSOT Rule: All writes must go through YazlikRezervasyon.
     * Event exists only for backward compatibility with existing code.
     * New code must NOT use this model.
     *
     * @deprecated 2026-07-06
     */
    protected $fillable = [
        'ilan_id',
        'check_in',
        'check_out',
        'musteri_adi',
        'musteri_telefon',
        'musteri_email',
        'misafir_sayisi',
        'toplam_fiyat',
        'rezervasyon_durumu',
        'ozel_istekler',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'misafir_sayisi' => 'integer',
        'toplam_fiyat' => 'decimal:2',
    ];

    /**
     * @deprecated 2026-07-06 — Use YazlikRezervasyon::ilan()
     */
    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }
}
