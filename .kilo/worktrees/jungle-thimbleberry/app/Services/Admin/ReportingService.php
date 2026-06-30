<?php

namespace App\Services\Admin;

use App\Models\YazlikFiyatlandirma;
use App\Models\YazlikRezervasyon;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ReportingService
{
    /**
     * Calculate Occupancy Rate (Doluluk Oranı).
     * CACHE-READY WRAPPER
     */
    public function calculateOccupancy(int $listingId, Carbon $startDate, Carbon $endDate): float
    {
        if (config('reporting.cache_enabled', false)) {
            $key = "reporting:occupancy:{$listingId}:{$startDate->format('Ymd')}:{$endDate->format('Ymd')}";
            return Cache::remember($key, 3600, function () use ($listingId, $startDate, $endDate) {
                return $this->calculateOccupancyRaw($listingId, $startDate, $endDate);
            });
        }

        return $this->calculateOccupancyRaw($listingId, $startDate, $endDate);
    }

    /**
     * Raw Logic for Occupancy
     */
    protected function calculateOccupancyRaw(int $listingId, Carbon $startDate, Carbon $endDate): float
    {
        $totalDays = $startDate->diffInDays($endDate) + 1; // Inclusive

        // 1. Get Blocked Days (Pricing inactive / Maintenance)
        $blockedRanges = YazlikFiyatlandirma::where('ilan_id', $listingId)
            ->where('aktiflik_durumu', false)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('baslangic_tarihi', [$startDate, $endDate])
                  ->orWhereBetween('bitis_tarihi', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('baslangic_tarihi', '<=', $startDate)
                         ->where('bitis_tarihi', '>=', $endDate);
                  });
            })
            ->get();

        $blockedDaysCount = 0;
        foreach ($blockedRanges as $range) {
            $rangeStart = Carbon::parse($range->baslangic_tarihi);
            $rangeEnd = Carbon::parse($range->bitis_tarihi);

            $effectiveStart = $rangeStart->max($startDate);
            $effectiveEnd = $rangeEnd->min($endDate);

            if ($effectiveEnd->gte($effectiveStart)) {
                $blockedDaysCount += $effectiveStart->diffInDays($effectiveEnd) + 1;
            }
        }

        $availableDays = $totalDays - $blockedDaysCount;
        if ($availableDays <= 0) {
            return 0.0;
        }

        // 2. Get Booked Days (Confirmed + Pending)
        $reservations = YazlikRezervasyon::where('ilan_id', $listingId)
            ->whereIn('rezervasyon_durumu', ['onaylandi', 'beklemede'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('check_in', [$startDate, $endDate])
                  ->orWhereBetween('check_out', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('check_in', '<=', $startDate)
                         ->where('check_out', '>=', $endDate);
                  });
            })
            ->get();

        $bookedDaysCount = 0;
        foreach ($reservations as $res) {
            $resCheckIn = Carbon::parse($res->check_in);
            $resCheckOut = Carbon::parse($res->check_out);

            $reportEndExplicit = $endDate->copy()->addDay();

            $actualStart = $resCheckIn->max($startDate);
            $actualEnd = $resCheckOut->min($reportEndExplicit);

            if ($actualEnd->gt($actualStart)) {
                $bookedDaysCount += $actualStart->diffInDays($actualEnd);
            }
        }

        $bookedDaysCount = min($bookedDaysCount, $availableDays);

        return round(($bookedDaysCount / $availableDays) * 100, 2);
    }

    /**
     * Calculate ADR (Average Daily Rate).
     * CACHE-READY WRAPPER
     */
    public function calculateADR(int $listingId, Carbon $startDate, Carbon $endDate): float
    {
        if (config('reporting.cache_enabled', false)) {
            $key = "reporting:adr:{$listingId}:{$startDate->format('Ymd')}:{$endDate->format('Ymd')}";
            return Cache::remember($key, 3600, function () use ($listingId, $startDate, $endDate) {
                return $this->calculateADRRaw($listingId, $startDate, $endDate);
            });
        }

        return $this->calculateADRRaw($listingId, $startDate, $endDate);
    }

    /**
     * Raw Logic for ADR
     */
    protected function calculateADRRaw(int $listingId, Carbon $startDate, Carbon $endDate): float
    {
        $reservations = YazlikRezervasyon::where('ilan_id', $listingId)
            ->whereIn('rezervasyon_durumu', ['onaylandi', 'beklemede'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('check_in', [$startDate, $endDate])
                  ->orWhereBetween('check_out', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('check_in', '<=', $startDate)
                         ->where('check_out', '>=', $endDate);
                  });
            })
            ->get();

        $totalRevenue = 0;
        $totalBookedNights = 0;

        $reportEndExplicit = $endDate->copy()->addDay();

        foreach ($reservations as $res) {
            $nights = $res->konaklama_suresi; // Uses fixed accessor
            if ($nights <= 0) continue;

            $dailyRate = $res->toplam_fiyat / $nights;

            $resCheckIn = Carbon::parse($res->check_in);
            $resCheckOut = Carbon::parse($res->check_out);

            // Calc overlap nights
            $actualStart = $resCheckIn->max($startDate);
            $actualEnd = $resCheckOut->min($reportEndExplicit);

            if ($actualEnd->gt($actualStart)) {
                $overlapNights = $actualStart->diffInDays($actualEnd);

                // Pro-rate Revenue
                $totalRevenue += ($dailyRate * $overlapNights);
                $totalBookedNights += $overlapNights;
            }
        }

        if ($totalBookedNights == 0) return 0.0;

        return round($totalRevenue / $totalBookedNights, 2);
    }

    /**
     * Calculate RevPAR (Revenue Per Available Room).
     * CACHE-READY WRAPPER
     */
    public function calculateRevPAR(int $listingId, Carbon $startDate, Carbon $endDate): float
    {
        if (config('reporting.cache_enabled', false)) {
            $key = "reporting:revpar:{$listingId}:{$startDate->format('Ymd')}:{$endDate->format('Ymd')}";
            return Cache::remember($key, 3600, function () use ($listingId, $startDate, $endDate) {
                return $this->calculateRevPARRaw($listingId, $startDate, $endDate);
            });
        }

        return $this->calculateRevPARRaw($listingId, $startDate, $endDate);
    }

    /**
     * Raw Logic for RevPAR
     */
    protected function calculateRevPARRaw(int $listingId, Carbon $startDate, Carbon $endDate): float
    {
        // Use the public methods to benefit from their caching if enabled/called independently
        $occupancyRate = $this->calculateOccupancy($listingId, $startDate, $endDate);
        if ($occupancyRate <= 0) return 0.0;

        $adr = $this->calculateADR($listingId, $startDate, $endDate);

        return round($adr * ($occupancyRate / 100), 2);
    }
}
