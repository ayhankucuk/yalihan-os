<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SAB Phase 17C: Rental Gelir Kalemi
 *
 * kalem_turu mapping (tinyint):
 *   0 = KIRA
 *   1 = DEPOZITO
 *   2 = EK_GELIR
 */
class RentalGelirKalemi extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'rental_gelir_kalemleri';

    // Kalem türü sabitleri
    public const KALEM_KIRA      = 0;
    public const KALEM_DEPOZITO  = 1;
    public const KALEM_EK_GELIR  = 2;

    public const KALEM_LABELS = [
        self::KALEM_KIRA     => 'Kira',
        self::KALEM_DEPOZITO => 'Depozito',
        self::KALEM_EK_GELIR => 'Ek Gelir',
    ];

    protected $fillable = [
        'ev_karti_id',
        'kalem_turu',
        'tutar',
        'para_birimi',
        'donem_yil',
        'donem_ay',
        'odeme_tarihi',
        'gelir_tarihi',
        'gelir_tipi',
        'aciklama',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'kalem_turu'    => 'integer',
        'gelir_tipi'    => 'integer',
        'tutar'         => 'float',
        'donem_yil'     => 'integer',
        'donem_ay'      => 'integer',
        'odeme_tarihi'  => 'date',
        'gelir_tarihi'  => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = $model->created_by ?? auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    // ── İlişkiler ──

    public function evKarti(): BelongsTo
    {
        return $this->belongsTo(RentalEvKarti::class, 'ev_karti_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Helpers ──

    public function getKalemTuruEtiketiAttribute(): string
    {
        return self::KALEM_LABELS[$this->kalem_turu] ?? 'Bilinmeyen';
    }
}
