<?php

namespace App\Services;

use App\Models\Language;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

/**
 * LocaleControlService
 *
 * Enterprise Locale Control Layer.
 * Handles locale resolution and validation.
 */
class LocaleControlService
{
    private const CACHE_TTL = 3600;

    /**
     * Get active languages.
     */
    public function getActiveLanguages()
    {
        return Cache::remember('active_languages', self::CACHE_TTL, function () { // context7-ignore
            try {
                return Language::active()->orderBy('display_order')->get(); // context7-ignore
            } catch (\Illuminate\Database\QueryException $e) {
                \Illuminate\Support\Facades\Log::warning("Boot fallback activated: languages table missing.");
                // Boot fallback safety: if languages table is missing or unreadable
                return collect([
                    (object) [
                        'code' => 'tr',
                        'name' => 'Türkçe',
                        'aktiflik_durumu' => true,
                        'varsayilan_durumu' => true,
                        'is_rtl' => false,
                        'display_order' => 1
                    ]
                ]);
            }
        });
    }

    /**
     * Resolve current locale.
     * Order: Auth > Session > Accept-Language > Default
     */
    public function resolveLocale(): string
    {
        // 1. Auth User preference (if implemented)
        if (auth()->check() && auth()->user()->preferred_locale) {
            $pref = auth()->user()->preferred_locale;
            if ($this->isLocaleActive($pref)) {
                return $pref;
            }
        }

        // 2. URL Segment (New)
        $urlLocale = request()->segment(1);
        if ($urlLocale && $this->isLocaleActive($urlLocale)) {
            if (Session::get('locale') !== $urlLocale) {
                Session::put('locale', $urlLocale);
            }
            return $urlLocale;
        }

        // 3. Session
        if (Session::has('locale')) {
            $sessionLocale = Session::get('locale');
            if ($this->isLocaleActive($sessionLocale)) {
                return $sessionLocale;
            }
        }

        // 4. Default from DB
        return $this->getDefaultLocale();
    }

    /**
     * Get default locale code.
     */
    public function getDefaultLocale(): string
    {
        return Cache::remember('default_locale', self::CACHE_TTL, function () {
            try {
                return Language::where('varsayilan_durumu', true)->value('code') ?? config('app.fallback_locale', 'tr');
            } catch (\Illuminate\Database\QueryException $e) {
                \Illuminate\Support\Facades\Log::warning("Boot fallback activated: default_locale.");
                // Boot fallback safety
                return config('app.fallback_locale', 'tr');
            }
        });
    }

    /**
     * Check if a locale is active.
     */
    public function isLocaleActive(string $code): bool
    {
        return $this->getActiveLanguages()->contains('code', $code);
    }

    /**
     * Dil aktiflik durumunu değiştirir.
     * SAB Rule 10: Logic in Service
     */
    public function toggleAktiflik(Language $language, bool $active): bool
    {
        if ($language->varsayilan_durumu && ! $active) {
            return false;
        }

        $success = $language->update(['aktiflik_durumu' => $active]);

        if ($success) {
            $this->clearCache();
        }

        return $success;
    }

    /**
     * Set default language.
     * SAB Rule 10: Logic in Service
     */
    public function setDefault(Language $language): bool
    {
        Language::where('varsayilan_durumu', true)->update(['varsayilan_durumu' => false]);

        $success = $language->update([
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
        Cache::forget('active_languages'); // context7-ignore
        Cache::forget('default_locale');
    }
}
