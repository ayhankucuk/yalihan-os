<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Season Model - Sezonluk fiyatlandırma sistemi
 * TatildeKirala/Airbnb tarzı dynamic pricing
 */
class Season extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'yazlik_fiyatlandirma';

    protected $fillable = [
        'ilan_id',
        'sezon_tipi', // ✅ SAB: Tablodaki gerçek kolon adı
        'baslangic_tarihi', // ✅ SAB: Tablodaki gerçek kolon adı
        'bitis_tarihi', // ✅ SAB: Tablodaki gerçek kolon adı
        'gunluk_fiyat', // ✅ SAB: Tablodaki gerçek kolon adı
        'haftalik_fiyat', // ✅ SAB: Tablodaki gerçek kolon adı
        'aylik_fiyat', // ✅ SAB: Tablodaki gerçek kolon adı
        'minimum_konaklama', // ✅ SAB: Tablodaki gerçek kolon adı
        'maksimum_konaklama', // ✅ SAB: Tablodaki gerçek kolon adı
        'ozel_gunler', // ✅ SAB: Tablodaki gerçek kolon adı
        // ✅ SAB: S-tatus replaced by aktiflik_durumu
    ];

    protected $casts = [
        'baslangic_tarihi' => 'date',
        'bitis_tarihi' => 'date',
        'gunluk_fiyat' => 'decimal:2',
        'haftalik_fiyat' => 'decimal:2',
        'aylik_fiyat' => 'decimal:2',
        'minimum_konaklama' => 'integer',
        'maksimum_konaklama' => 'integer',
        'ozel_gunler' => 'array',
        'is_active' => \App\Enums\AktiflikDurumu::class,
    ];

    /**
     * Relationship: İlan
     */
    public function ilan()
    {
        return $this->belongsTo(Ilan::class);
    }

    /**
     * Scope: Aktif sezonlar
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', \App\Enums\AktiflikDurumu::AKTIF->value);
    }

    /**
     * Scope: Sezon tipine göre
     */
    public function scopeByType($query, $type)
    {
        return $query->where('sezon_tipi', $type);
    }

    /**
     * Scope: Belirli bir tarihi kapsayan sezonlar
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('baslangic_tarihi', '<=', $date)
            ->where('bitis_tarihi', '>=', $date)
            ->active(); // context7-ignore
    }

    /**
     * Scope: Tarih aralığını kapsayan sezonlar
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('baslangic_tarihi', [$startDate, $endDate])
                ->orWhereBetween('bitis_tarihi', [$startDate, $endDate])
                ->orWhere(function ($q) use ($startDate, $endDate) {
                    $q->where('baslangic_tarihi', '<=', $startDate)
                        ->where('bitis_tarihi', '>=', $endDate);
                });
        })->active(); // context7-ignore
    }

    /**
     * Backward compatibility accessors
     */
    public function getStartDateAttribute()
    {
        return $this->baslangic_tarihi;
    }

    public function getEndDateAttribute()
    {
        return $this->bitis_tarihi;
    }

    public function getTypeAttribute()
    {
        return $this->sezon_tipi;
    }

    public function getDailyPriceAttribute()
    {
        return $this->gunluk_fiyat;
    }

    public function getWeeklyPriceAttribute()
    {
        return $this->haftalik_fiyat;
    }

    public function getMonthlyPriceAttribute()
    {
        return $this->aylik_fiyat;
    }

    public function getMinimumStayAttribute()
    {
        return $this->minimum_konaklama;
    }

    public function getMaximumStayAttribute()
    {
        return $this->maksimum_konaklama;
    }

    public function getIsActiveAttribute()
    {
        return (bool) $this->aktiflik_durumu;
    }

    /**
     * Canonical yayın_aktiflik_durumuu accessors mapping legacy column.
     */
    public function getYayinStatusuAttribute()
    {
        return null; // No active flag column defined in schema
    }

    public function setYayinStatusuAttribute($value): void
    {
        // No-op: active flag column not present
    }

    /**
     * Helper: Belirli bir tarih için fiyat getir
     *
     * @param  string  $date
     * @param  bool  $isWeekend
     * @return float|null
     */
    public function getPriceForDate($date, $isWeekend = false)
    {
        // Özel günler kontrolü (ozel_gunler JSON'dan)
        if ($this->ozel_gunler && is_array($this->ozel_gunler)) {
            $dateKey = Carbon::parse($date)->format('Y-m-d');
            if (isset($this->ozel_gunler[$dateKey])) {
                return (float) $this->ozel_gunler[$dateKey];
            }
        }

        return (float) $this->gunluk_fiyat;
    }

    /**
     * Helper: Tarih aralığı için toplam fiyat hesapla
     *
     * @param  string  $checkIn
     * @param  string  $checkOut
     * @return array
     */
    public function calculatePrice($checkIn, $checkOut)
    {
        $checkInDate = Carbon::parse($checkIn);
        $checkOutDate = Carbon::parse($checkOut);
        $nightCount = $checkOutDate->diffInDays($checkInDate);

        // Günlük toplam hesapla
        $dailyTotal = 0;
        $currentDate = $checkInDate->copy();

        for ($i = 0; $i < $nightCount; $i++) {
            $isWeekend = $currentDate->isWeekend();
            $dailyTotal += $this->getPriceForDate($currentDate, $isWeekend);
            $currentDate->addDay();
        }

        // Haftalık/aylık indirim kontrolü
        $finalPrice = $dailyTotal;

        if ($nightCount >= 30 && $this->aylik_fiyat) {
            // Aylık indirim
            $monthCount = floor($nightCount / 30);
            $remainingDays = $nightCount % 30;
            $finalPrice = ($this->aylik_fiyat * $monthCount) + ($this->gunluk_fiyat * $remainingDays);
        } elseif ($nightCount >= 7 && $this->haftalik_fiyat) {
            // Haftalık indirim
            $weekCount = floor($nightCount / 7);
            $remainingDays = $nightCount % 7;
            $finalPrice = ($this->haftalik_fiyat * $weekCount) + ($this->gunluk_fiyat * $remainingDays);
        }

        return [
            'night_count' => $nightCount,
            'base_price' => $dailyTotal,
            'final_price' => $finalPrice,
            'total_price' => $finalPrice,
            'currency' => 'TRY', // para_birimi ilanlar tablosunda
        ];
    }

    /**
     * Helper: Minimum konaklama kontrolü
     */
    public function meetsMinimumStay($nightCount)
    {
        return $nightCount >= $this->minimum_konaklama;
    }

    /**
     * Helper: Maksimum konaklama kontrolü
     */
    public function meetsMaximumStay($nightCount)
    {
        if (! $this->maksimum_konaklama) {
            return true;
        }

        return $nightCount <= $this->maksimum_konaklama;
    }

    /**
     * Static: Belirli tarih için en uygun sezonu bul
     */
    public static function findBestForDate($ilanId, $date)
    {
        return static::where('ilan_id', $ilanId)
            ->forDate($date)
            ->orderBy('baslangic_tarihi', 'desc') // context7-ignore
            ->first();
    }

    /**
     * Static: Tarih aralığı için fiyat hesapla
     */
    public static function calculatePriceForDateRange($ilanId, $checkIn, $checkOut)
    {
        $season = static::where('ilan_id', $ilanId)
            ->forDateRange($checkIn, $checkOut)
            ->orderBy('baslangic_tarihi', 'desc') // ✅ SAB: Tablodaki gerçek kolon adı // context7-ignore
            ->first();

        if (! $season) {
            return null;
        }

        return $season->calculatePrice($checkIn, $checkOut);
    }
}
