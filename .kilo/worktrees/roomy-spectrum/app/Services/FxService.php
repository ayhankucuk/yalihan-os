<?php

namespace App\Services;

use App\Models\FxRate;
use Illuminate\Support\Facades\Cache;

/**
 * FxService — Foreign Exchange Rate Management
 *
 * Rules:
 * - Base currency is always TRY
 * - Rates are fetched from fx_rates table (DB-seeded, not hardcoded)
 * - Rate is LOCKED at transaction time (historical integrity)
 * - Display currency is for reporting ONLY
 */
class FxService
{
    private const BASE_CURRENCY = 'TRY';
    private const CACHE_TTL     = 1800; // 30 minutes

    /**
     * Get the current rate: 1 TRY = X {currency}
     */
    public function getRate(string $toCurrency): float
    {
        if ($toCurrency === self::BASE_CURRENCY) {
            return 1.0;
        }

        $cacheKey = "fx_rate.TRY.{$toCurrency}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($toCurrency) {
            $rate = FxRate::where('from_currency', self::BASE_CURRENCY)
                ->where('to_currency', $toCurrency)
                ->where('aktiflik_durumu', true)
                ->orderByDesc('effective_at') // context7-ignore
                ->value('rate');

            if (!$rate) {
                throw new \RuntimeException("FX rate not found for TRY → {$toCurrency}. Please seed fx_rates table.");
            }

            return (float) $rate;
        });
    }

    /**
     * Lock the current rate at transaction time.
     * Returns the rate used (persisted in the ledger record).
     */
    public function lockRate(string $toCurrency): float
    {
        return $this->getRate($toCurrency); // Same as getRate — semantically "locks" at current snapshot
    }

    /**
     * Convert TRY amount to display currency.
     */
    public function convertFromTRY(float $amountTRY, string $toCurrency, ?float $lockedRate = null): float
    {
        $rate = $lockedRate ?? $this->getRate($toCurrency);
        return round($amountTRY * $rate, 2);
    }

    /**
     * Convert any currency to TRY base.
     */
    public function convertToTRY(float $amount, string $fromCurrency, ?float $lockedRate = null): float
    {
        if ($fromCurrency === self::BASE_CURRENCY) {
            return $amount;
        }

        $rate = $lockedRate ?? $this->getRate($fromCurrency);

        if ($rate == 0) {
            throw new \RuntimeException("Cannot divide by zero FX rate.");
        }

        return round($amount / $rate, 2);
    }

    /**
     * Invalidate cached rates (call after seeding new rates)
     */
    public function flushRateCache(): void
    {
        // Pattern-based requires cache tags. Call per-currency explicitly.
        foreach (['EUR', 'GBP', 'USD'] as $currency) {
            Cache::forget("fx_rate.TRY.{$currency}");
        }
    }
}
