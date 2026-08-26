<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Event extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'yazlik_rezervasyonlar';

    protected $fillable = [
        'ilan_id',
        'giris_tarihi',
        'cikis_tarihi',
        'musteri_adi',
        'musteri_telefon',
        'musteri_email',
        'misafir_sayisi',
        'toplam_tutar',
        'durum',
        'ozel_istekler',
    ];

    protected $casts = [
        'giris_tarihi' => 'date',
        'cikis_tarihi' => 'date',
        'misafir_sayisi' => 'integer',
        'toplam_tutar' => 'decimal:2',
    ];

    public function ilan(): BelongsTo
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    /**
     * Tarih aralığı sorgulama scope'u
     */
    public function scopeBetweenDates(Builder $query, $start, $end = null): Builder
    {
        $startDate = $start instanceof Carbon ? $start->toDateString() : $start;
        $endDate = $end instanceof Carbon ? $end->toDateString() : ($end ?? $startDate);

        return $query->where(function (Builder $q) use ($startDate, $endDate) {
            $q->where('giris_tarihi', '<=', $endDate)
              ->where('cikis_tarihi', '>=', $startDate);
        });
    }

    /**
     * Belirli bir ilan için tarih çakışması kontrolü
     */
    public static function hasConflict(int $ilanId, string $start, string $end): bool
    {
        $startDate = Carbon::parse($start)->toDateString();
        $endDate = Carbon::parse($end)->toDateString();

        return static::where('ilan_id', $ilanId)
            ->where('durum', 'Onaylandı')
            ->where(function (Builder $q) use ($startDate, $endDate) {
                $q->where('giris_tarihi', '<=', $endDate)
                  ->where('cikis_tarihi', '>=', $startDate);
            })
            ->exists();
    }
}
