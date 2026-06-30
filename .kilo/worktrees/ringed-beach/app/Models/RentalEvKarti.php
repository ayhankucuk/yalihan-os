<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * SAB Phase 17C: Rental Ev Kartı
 *
 * Bir mülkün kiralama yönetim kartı.
 * Gelir/gider kalemleri bu karta bağlı.
 */
class RentalEvKarti extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'rental_ev_kartlari';

    protected $fillable = [
        'ilan_id',
        'baslik',
        'adres',
        'su_abone_id',
        'elektrik_abone_id',
        'internet_abone_id',
        'abonelik_su',
        'abonelik_elektrik',
        'abonelik_dogalgaz',
        'aidat',
        'depozito_tutari',
        'para_birimi',
        'depozito_alinma_tarihi',
        'depozito_iade_tarihi',
        'aciklama',
        'notlar',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'aidat'                 => 'float',
        'depozito_tutari'       => 'float',
        'depozito_alinma_tarihi'=> 'date',
        'depozito_iade_tarihi'  => 'date',
        'is_active'       => \App\Enums\AktiflikDurumu::class,
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

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    public function gelirler(): HasMany
    {
        return $this->hasMany(RentalGelirKalemi::class, 'ev_karti_id');
    }

    public function giderler(): HasMany
    {
        return $this->hasMany(RentalGiderKalemi::class, 'ev_karti_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
