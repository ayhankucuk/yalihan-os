<?php

namespace App\Services;

use App\Enums\IlanDurumu;

use App\Models\Event;
use App\Models\Il;
use App\Models\Ilan;
use App\Models\Mahalle;
use App\Models\IlanKategori;
use App\Models\Season;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Villa (Yazlık Kiralama) İş Mantığı Servisi
 */
class VillaService
{
    public function getYazlikKategori(): ?IlanKategori
    {
        return IlanKategori::where('slug', 'yazlik-kiralama')->first();
    }

    public function searchVillas(
        int $kategoriId,
        array $filters,
        string $sortBy = 'popular',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator
    {
        $query = Ilan::with(['photos', 'featuredPhoto', 'il', 'ilce', 'mahalle', 'seasons'])
            ->where('ana_kategori_id', $kategoriId)
            ->whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'yayinda']);

        if (!empty($filters['location'])) {
            $locations = is_array($filters['location']) ? $filters['location'] : [$filters['location']];
            $locations = array_values(array_filter($locations));
            if (!empty($locations)) {
                $query->whereHas('mahalle', fn ($q) => $q->whereIn('mahalle_adi', $locations));
            }
        }

        if (!empty($filters['guests'])) {
            $guests = (int) $filters['guests'];
            $query->where('maksimum_misafir', '>=', $guests);
        }

        $query->priceRange(
            !empty($filters['min_price']) ? (float) $filters['min_price'] : null,
            !empty($filters['max_price']) ? (float) $filters['max_price'] : null,
            'gunluk_fiyat'
        );

        if (!empty($filters['check_in']) && !empty($filters['check_out'])) {
            $checkIn = $filters['check_in'];
            $checkOut = $filters['check_out'];

            $query->whereDoesntHave('events', function ($q) use ($checkIn, $checkOut) {
                $q->where('rezervasyon_durumu', 'Onaylandı')->betweenDates($checkIn, $checkOut);
            });
        }

        if (!empty($filters['amenities'])) {
            foreach ($filters['amenities'] as $amenity) {
                $query->whereHas('features', fn ($q) => $q->where('features.slug', $amenity));
            }
        }

        // rental_type filtresi: view'dan Türkçe kısa değer (sezonluk/aylik/gunluk) gelir, DB'de İngilizce (seasonal/monthly/daily)
        if (!empty($filters['rental_type'])) {
            $typeMap = [
                'sezonluk' => 'seasonal',
                'aylik'    => 'monthly',
                'gunluk'   => 'daily',
                // İngilizce doğrudan gelirse de destekle
                'seasonal' => 'seasonal',
                'monthly'  => 'monthly',
                'daily'    => 'daily',
                'weekly'   => 'weekly',
            ];
            $dbType = $typeMap[$filters['rental_type']] ?? null;
            if ($dbType) {
                $query->where('rental_type', $dbType);
            }
        }

        $sortMap = [
            'price_low' => ['gunluk_fiyat', 'asc'],
            'price_high' => ['gunluk_fiyat', 'desc'],
            'newest' => ['created_at', 'desc'],
            'popular' => ['view_count', 'desc'],
        ];

        if (isset($sortMap[$sortBy])) {
            [$sortColumn, $sortDirection] = $sortMap[$sortBy];
            $query->orderBy($sortColumn, $sortDirection); // context7-ignore
        } else {
            $query->sort($sortBy, $sortDirection, 'view_count');
        }

        return $query->paginate(24);
    }

    public function getFilterLocations(int $kategoriId): Collection
    {
        // Mahalle bazında — sadece bu kategoride ilanı olan mahalleler, ilan sayısıyla birlikte
        $mahalleler = Mahalle::withCount(['ilanlar as ilan_sayisi' => function ($q) use ($kategoriId) {
                $q->where('ana_kategori_id', $kategoriId)
                  ->whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'yayinda']);
            }])
            ->having('ilan_sayisi', '>', 0)
            ->orderByDesc('ilan_sayisi')
            ->orderBy('mahalle_adi')
            ->get(['id', 'mahalle_adi']);

        return $mahalleler;
    }

    public function getVillaDetail(int $id): Ilan
    {
        $villa = Ilan::with([
            'photos',
            'featuredPhoto',
            'il',
            'ilce',
            'mahalle',
            'features',
            'seasons' => fn ($q) => $q->where('aktiflik_durumu', 1),
            'events' => fn ($q) => $q->where('rezervasyon_durumu', 'Onaylandı'),
        ])->where('yayin_durumu', 'yayinda')->findOrFail($id);

        $villa->increment('view_count');

        return $villa;
    }

    public function getAvailabilityCalendar(int $villaId, int $months = 3): array
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->addMonths($months)->endOfMonth();

        $events = Event::where('ilan_id', $villaId)
            ->where('rezervasyon_durumu', 'Onaylandı')
            ->betweenDates($startDate, $endDate)
            ->get(['check_in', 'check_out', 'rezervasyon_durumu']);

        $calendar = [];
        foreach ($events as $event) {
            $current = Carbon::parse($event->check_in);
            $end = Carbon::parse($event->check_out);

            while ($current->lte($end)) {
                $calendar[$current->format('Y-m-d')] = [
                    'available' => false,
                    'yayin_durumu' => $event->rezervasyon_durumu,
                ];
                $current->addDay();
            }
        }

        return $calendar;
    }

    public function getPricingInfo(Ilan $villa): array
    {
        $seasons = $villa->seasons()->where('aktiflik_durumu', 1)->get();

        return [
            'daily_min' => $seasons->min('daily_price') ?? $villa->gunluk_fiyat,
            'daily_max' => $seasons->max('daily_price') ?? $villa->gunluk_fiyat,
            'weekly' => $seasons->first()->weekly_price ?? null,
            'monthly' => $seasons->first()->monthly_price ?? null,
            'currency' => $villa->para_birimi ?? 'TRY',
            'seasons' => $seasons,
        ];
    }

    public function getSimilarVillas(Ilan $villa, int $limit = 4): Collection
    {
        return Ilan::with(['featuredPhoto', 'il', 'ilce'])
            ->where('id', '!=', $villa->id)
            ->where('ana_kategori_id', $villa->ana_kategori_id)
            ->where('il_id', $villa->il_id)
            ->where('yayin_durumu', 'yayinda')
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function checkAvailabilityAndPrice(int $villaId, string $checkIn, string $checkOut): array
    {
        $hasConflict = Event::hasConflict($villaId, $checkIn, $checkOut);

        if ($hasConflict) {
            return [
                'available' => false,
                'message' => 'Seçtiğiniz tarihler dolu. Lütfen başka tarih seçin.',
                'pricing' => null
            ];
        }

        $pricing = Season::calculatePriceForDateRange($villaId, $checkIn, $checkOut);

        if (! $pricing) {
            $villa = Ilan::select('gunluk_fiyat', 'para_birimi')->findOrFail($villaId);
            $nightCount = Carbon::parse($checkOut)->diffInDays(Carbon::parse($checkIn));
            $pricing = [
                'night_count' => $nightCount,
                'daily_price' => $villa->gunluk_fiyat ?? 0,
                'total_price' => ($villa->gunluk_fiyat ?? 0) * $nightCount,
                'currency' => $villa->para_birimi ?? 'TRY',
            ];
        }

        return [
            'available' => true,
            'message' => 'Tarihler müsait!',
            'pricing' => $pricing,
        ];
    }

    public function getAvailableToday(int $kategoriId): int
    {
        $today = Carbon::today();

        return Ilan::where('ana_kategori_id', $kategoriId)
            ->where('yayin_durumu', 'yayinda')
            ->whereDoesntHave('events', function ($q) use ($today) {
                $q->where('rezervasyon_durumu', 'Onaylandı')
                    ->where('check_in', '<=', $today)
                    ->where('check_out', '>', $today);
            })
            ->count();
    }
}
