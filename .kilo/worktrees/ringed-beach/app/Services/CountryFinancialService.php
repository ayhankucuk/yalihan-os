<?php

namespace App\Services;

use App\Models\CountryFinancialRule;
use Illuminate\Support\Facades\Cache;

/**
 * CountryFinancialService
 *
 * Reads ALL rates from DB (country_financial_rules table).
 * Hardcoded rates are FORBIDDEN by SAB Financial Constitution.
 *
 * Supports:
 * - Rental commission (TR/GR/UK)
 * - Sales commission
 * - Advisory fee (GR: Golden Visa)
 * - Tax layer
 */
class CountryFinancialService
{
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Resolve and cache the rule for a country.
     */
    public function getRule(string $countryCode): CountryFinancialRule
    {
        $cacheKey = "country_rule.{$countryCode}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($countryCode) {
            $rule = CountryFinancialRule::where('country_code', $countryCode)
                ->where('aktiflik_durumu', true)
                ->first();

            if (!$rule) {
                throw new \RuntimeException("No active financial rule for country: {$countryCode}. Seed country_financial_rules.");
            }

            return $rule;
        });
    }

    /**
     * Calculate rental commission (TRY).
     */
    public function calculateRentalCommission(float $baseAmountTRY, string $countryCode): array
    {
        $rule = $this->getRule($countryCode);
        $commission = round($baseAmountTRY * $rule->rental_commission_rate, 2);

        return [
            'rate'        => $rule->rental_commission_rate,
            'amount_try'  => $commission,
            'country'     => $countryCode,
        ];
    }

    /**
     * Calculate sales commission (TRY).
     */
    public function calculateSalesCommission(float $baseAmountTRY, string $countryCode): array
    {
        $rule = $this->getRule($countryCode);
        $commission = round($baseAmountTRY * $rule->sales_commission_rate, 2);

        return [
            'rate'       => $rule->sales_commission_rate,
            'amount_try' => $commission,
            'country'    => $countryCode,
        ];
    }

    /**
     * Calculate advisory fee (e.g. GR Golden Visa).
     */
    public function calculateAdvisory(float $baseAmountTRY, string $countryCode): array
    {
        $rule = $this->getRule($countryCode);
        $fee  = round($baseAmountTRY * $rule->advisory_fee_rate, 2);

        return [
            'rate'       => $rule->advisory_fee_rate,
            'amount_try' => $fee,
            'country'    => $countryCode,
        ];
    }

    /**
     * Calculate tax (e.g. KDV/VAT).
     */
    public function calculateTax(float $baseAmountTRY, string $countryCode): array
    {
        $rule = $this->getRule($countryCode);
        $tax  = round($baseAmountTRY * $rule->tax_rate, 2);

        return [
            'rate'       => $rule->tax_rate,
            'amount_try' => $tax,
            'country'    => $countryCode,
        ];
    }

    /**
     * Full financial breakdown for a rental reservation.
     */
    public function rentalBreakdown(float $subtotalTRY, string $countryCode): array
    {
        $commission = $this->calculateRentalCommission($subtotalTRY, $countryCode);
        $advisory   = $this->calculateAdvisory($subtotalTRY, $countryCode);
        $tax        = $this->calculateTax($subtotalTRY, $countryCode);
        $total      = round($subtotalTRY + $commission['amount_try'] + $advisory['amount_try'] + $tax['amount_try'], 2);

        return [
            'subtotal_try'    => $subtotalTRY,
            'commission'      => $commission,
            'advisory'        => $advisory,
            'tax'             => $tax,
            'total_try'       => $total,
            'country'         => $countryCode,
        ];
    }

    /**
     * Flush country rule cache (call after updating rules).
     */
    public function flushCache(string $countryCode): void
    {
        Cache::forget("country_rule.{$countryCode}");
    }
}
