<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

/**
 * CurrencyControlService
 *
 * Enterprise Currency Control Layer.
 */
class CurrencyControlService
{
    private const CACHE_TTL = 3600;

    /**
     * Get active currencies.
     */
    public function getActiveCurrencies()
    {
        return Cache::remember('active_currencies', self::CACHE_TTL, function () { // context7-ignore
            return Currency::active()->get();
        });
    }

    /**
     * Resolve current currency.
     * Order: Auth > Session > Default
     */
    public function resolveCurrency(): string
    {
        // 1. Auth User preference
        if (auth()->check() && auth()->user()->preferred_currency) {
            $pref = auth()->user()->preferred_currency;
            if ($this->isCurrencyActive($pref)) {
                return $pref;
            }
        }

        // 2. Session
        if (Session::has('currency')) {
            $sessionCurrency = Session::get('currency');
            if ($this->isCurrencyActive($sessionCurrency)) {
                return $sessionCurrency;
            }
        }

        return $this->getDefaultCurrency();
    }

    /**
     * Get default currency code.
     */
    public function getDefaultCurrency(): string
    {
        return Cache::remember('default_currency', self::CACHE_TTL, function () {
            return Currency::where('varsayilan_durumu', true)->value('code') ?? 'TRY';
        });
    }

    /**
     * Check if a currency is active.
     */
    public function isCurrencyActive(string $code): bool
    {
        return $this->getActiveCurrencies()->contains('code', $code);
    }

    /**
     * Para birimi aktiflik durumunu değiştirir.
     * SAB Rule 10: Logic in Service
     */
    public function toggleAktiflik(Currency $currency, bool $active): bool
    {
        // TRY and Default cannot be deactivated
        if (($currency->code === 'TRY' || $currency->varsayilan_durumu) && ! $active) {
            return false;
        }

        $success = $currency->update(['aktiflik_durumu' => $active]);

        if ($success) {
            $this->clearCache();
        }

        return $success;
    }

    /**
     * Set default currency.
     * SAB Rule 10: Logic in Service
     */
    public function setDefault(Currency $currency): bool
    {
        Currency::where('varsayilan_durumu', true)->update(['varsayilan_durumu' => false]);

        $success = $currency->update([
            'varsayilan_durumu' => true,
            'aktiflik_durumu'   => true
        ]);

        if ($success) {
            $this->clearCache();
        }

        return $success;
    }

    /**
     * Clear cache.
     */
    public function clearCache(): void
    {
        Cache::forget('active_currencies'); // context7-ignore
        Cache::forget('default_currency');
    }
}
