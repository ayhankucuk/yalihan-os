<?php

namespace App\Services;

use App\Models\Ilan;
use App\Models\PropertySeasonalRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PropertyPricingService
 *
 * SINGLE SOURCE OF TRUTH for all rental pricing calculations.
 * UI only reads from this service. No pricing logic in controllers/views.
 *
 * Supports:
 *  - Base price fallback
 *  - Seasonal rate override (takes precedence over base)
 *  - Currency conversion (TRY → EUR, GBP)
 *  - Commission & tax layer (TR, GR, UK)
 *  - Min stay override per season
 *  - Deterministic, audit-logged calculation results
 */
class PropertyPricingService
{
    /**
     * Exchange rates (TRY base). In production, inject from a cache-backed currency service.
     * These are static fallbacks — replace with live rates via `CurrencyRateService` when available.
     */
    private const EXCHANGE_RATES = [
        'TRY' => 1.0,
        'EUR' => 0.029,   // 1 TRY ≈ 0.029 EUR
        'GBP' => 0.025,   // 1 TRY ≈ 0.025 GBP
    ];

    /**
     * Commission config per market (percentage, 0-1 float).
     */
    private const COMMISSION_RATES = [
        'TR' => 0.08,  // Türkiye: %8 service commission
        'GR' => 0.05,  // Yunanistan: %5 (Golden Visa advisory)
        'UK' => 0.06,  // İngiltere: %6 optional service margin
    ];

    /**
     * Calculate the total quote for a reservation.
     *
     * @param  int     $propertyId
     * @param  string  $checkIn     YYYY-MM-DD
     * @param  string  $checkOut    YYYY-MM-DD (exclusive)
     * @param  string  $market      Country code: TR / GR / UK
     * @param  string  $currency    Output currency: TRY / EUR / GBP
     * @return array{
     *     nights: int,
     *     nightly_rate_try: int,
     *     subtotal_try: int,
     *     commission_rate: float,
     *     commission_try: int,
     *     total_try: int,
     *     total_converted: float,
     *     currency: string,
     *     applied_season: string|null,
     *     min_stay_applicable: int,
     *     audit: array
     * }
     */
    public function calculateQuote(
        int    $propertyId,
        string $checkIn,
        string $checkOut,
        string $market   = 'TR',
        string $currency = 'TRY'
    ): array {
        $start  = Carbon::parse($checkIn)->startOfDay();
        $end    = Carbon::parse($checkOut)->startOfDay();
        $nights = $start->diffInDays($end);

        if ($nights < 1) {
            throw new \InvalidArgumentException("Invalid date range: nights must be >= 1.");
        }

        $ilan = Ilan::findOrFail($propertyId);

        // 1. Determine nightly rate (Seasonal takes precedence over base)
        [$nightlyRateTRY, $appliedSeason, $minStayApplicable] = $this->resolveNightlyRate(
            $propertyId,
            $start,
            $end,
            (int) $ilan->fiyat,
            (int) ($ilan->min_stay_nights ?? 1)
        );

        // 2. Subtotal
        $subtotalTRY = $nightlyRateTRY * $nights;

        // 3. Commission
        $commissionRate = self::COMMISSION_RATES[$market] ?? self::COMMISSION_RATES['TR'];
        $commissionTRY  = (int) round($subtotalTRY * $commissionRate);
        $totalTRY       = $subtotalTRY + $commissionTRY;

        // 4. Currency conversion
        $rate           = self::EXCHANGE_RATES[$currency] ?? 1.0;
        $totalConverted = round($totalTRY * $rate, 2);

        $audit = [
            'property_id'       => $propertyId,
            'check_in'          => $checkIn,
            'check_out'         => $checkOut,
            'nights'            => $nights,
            'base_price_try'    => (int) $ilan->fiyat,
            'applied_rate_try'  => $nightlyRateTRY,
            'season_applied'    => $appliedSeason,
            'market'            => $market,
            'commission_pct'    => $commissionRate * 100,
            'commission_try'    => $commissionTRY,
            'total_try'         => $totalTRY,
            'output_currency'   => $currency,
            'exchange_rate'     => $rate,
            'total_converted'   => $totalConverted,
            'calculated_at'     => now()->toIso8601String(),
        ];

        Log::channel('stack')->info('PricingService::calculateQuote', $audit);

        return [
            'nights'              => $nights,
            'nightly_rate_try'    => $nightlyRateTRY,
            'subtotal_try'        => $subtotalTRY,
            'commission_rate'     => $commissionRate,
            'commission_try'      => $commissionTRY,
            'total_try'           => $totalTRY,
            'total_converted'     => $totalConverted,
            'currency'            => $currency,
            'applied_season'      => $appliedSeason,
            'min_stay_applicable' => $minStayApplicable,
            'audit'               => $audit,
        ];
    }

    /**
     * Convert TRY amount to target currency.
     */
    public function convertFromTRY(int $amountTRY, string $targetCurrency): float
    {
        $rate = self::EXCHANGE_RATES[$targetCurrency] ?? 1.0;
        return round($amountTRY * $rate, 2);
    }

    /**
     * Get effective min_stay for a date range (seasonal override or property default).
     */
    public function getEffectiveMinStay(int $propertyId, string $checkIn): int
    {
        $ilan = Ilan::findOrFail($propertyId);
        $date = Carbon::parse($checkIn);

        $season = PropertySeasonalRate::where('property_id', $propertyId)
            ->where('aktiflik_durumu', true)
            ->where('start_date', '<=', $date->format('Y-m-d'))
            ->where('end_date', '>=', $date->format('Y-m-d'))
            ->whereNotNull('min_stay_override')
            ->first();

        return $season?->min_stay_override ?? (int) ($ilan->min_stay_nights ?? 1);
    }

    /**
     * Resolve nightly rate from seasonal config or fall back to base_price.
     * Returns [nightlyRate, seasonLabel|null, minStay].
     */
    private function resolveNightlyRate(
        int    $propertyId,
        Carbon $start,
        Carbon $end,
        int    $basePrice,
        int    $defaultMinStay
    ): array {
        // Find applicable season for the check-in date
        $season = PropertySeasonalRate::where('property_id', $propertyId)
            ->where('aktiflik_durumu', true)
            ->where('start_date', '<=', $start->format('Y-m-d'))
            ->where('end_date', '>=', $start->format('Y-m-d'))
            ->orderByDesc('start_date') // context7-ignore
            ->first();

        if ($season) {
            $rate     = (int) $season->nightly_rate;
            $minStay  = $season->min_stay_override ?? $defaultMinStay;
            $label    = $season->season_label;
        } else {
            $rate    = $basePrice;
            $minStay = $defaultMinStay;
            $label   = null;
        }

        return [$rate, $label, $minStay];
    }
}
