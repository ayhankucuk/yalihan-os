<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCountryScope;

/**
 * SAB Phase 17C: Rental Gider Kalemi
 *
 * kalem_turu mapping (tinyint):
 *   0 = ELEKTRIK
 *   1 = SU
 *   2 = TEMIZLIK
 *   3 = HAVUZ
 *   4 = BAHCIVAN
 *   5 = BAKIM
 *   6 = DIGER
 */
class RentalGiderKalemi extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'rental_gider_kalemleri';

    // Kalem türü sabitleri
    public const KALEM_ELEKTRIK  = 0;
    public const KALEM_SU        = 1;
    public const KALEM_TEMIZLIK  = 2;
    public const KALEM_HAVUZ     = 3;
    public const KALEM_BAHCIVAN  = 4;
    public const KALEM_BAKIM     = 5;
    public const KALEM_DIGER     = 6;

    public const KALEM_LABELS = [
        self::KALEM_ELEKTRIK => 'Elektrik',
        self::KALEM_SU       => 'Su',
        self::KALEM_TEMIZLIK => 'Temizlik',
        self::KALEM_HAVUZ    => 'Havuz',
        self::KALEM_BAHCIVAN => 'Bahçıvan',
        self::KALEM_BAKIM    => 'Bakım',
        self::KALEM_DIGER    => 'Diğer',
    ];

    protected $fillable = [
        'ev_karti_id',
        'kalem_turu',
        'tutar',
        'para_birimi',
        'donem_yil',
        'donem_ay',
        'odeme_tarihi',
        'gider_tarihi',
        'gider_kategorisi',
        'odeyen_taraf',
        'maliyeti_tasayan_taraf',
        'tedarikci',
        'aciklama',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'kalem_turu'             => 'integer',
        'tutar'                  => 'float',
        'donem_yil'              => 'integer',
        'donem_ay'               => 'integer',
        'odeme_tarihi'           => 'date',
        'gider_tarihi'           => 'date',
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
