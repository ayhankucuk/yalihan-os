<?php

namespace App\Services\Finance;

use Illuminate\Support\Facades\Config;

class PricingService
{
    /**
     * Calculate display price with potential discounts.
     *
     * @param float $dailyPrice
     * @param string $duration 'daily', 'weekly', 'monthly'
     * @return float
     */
    public function calculateRentalPrice(float $dailyPrice, string $duration = 'daily'): float
    {
        switch ($duration) {
            case 'weekly':
                $discount = Config::get('context7_pricing.discounts.weekly', 0.15);
                return $dailyPrice * 7 * (1 - $discount);
            case 'monthly':
                $discount = Config::get('context7_pricing.discounts.monthly', 0.30);
                return $dailyPrice * 30 * (1 - $discount);
            default:
                return $dailyPrice;
        }
    }

    public function getSmartFieldCost(): int
    {
        return Config::get('context7_pricing.smart_fields.price', 50);
    }

    public function getDefaultAverageUnitPrice(): int
    {
        return Config::get('context7_pricing.defaults.average_unit_price', 1_500_000);
    }

    public function getFinanceConfig(string $key, $default = null)
    {
        return Config::get('context7_pricing.finance.' . $key, $default);
    }

    public function getSeasonalConfig(string $key, $default = null)
    {
        return Config::get('context7_pricing.seasonal.' . $key, $default);
    }
}
