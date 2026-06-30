<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use App\Models\Ilan;

class SeoMetaService
{
    /**
     * Get Meta Tags for current page/locale.
     */
    public function getMeta(string $locale): array
    {
        $routeName = Route::currentRouteName();
        $defaults = config('seo.defaults.' . $locale, config('seo.defaults.en'));

        $meta = [
            'title'       => $defaults['title'] ?? 'Yalıhan Emlak',
            'description' => $defaults['description'] ?? '',
            'hreflang'    => $this->getHrefLangs(),
            'canonical'   => url()->current(),
            'og_locale'   => $this->getOgLocale($locale),
            'schema'      => $this->getSchema($routeName, $locale),
        ];

        // Specific overrides for landing pages
        switch ($routeName) {
            case 'public.invest-in-turkey':
                $meta['title'] = __('Invest in Turkey - High ROI Real Estate');
                $meta['description'] = __('Discover top yield properties in Turkey. Calculate your ROI and invest in Mediterranean real estate.');
                break;
            case 'public.golden-visa-greece':
                $meta['title'] = __('Greece Golden Visa - Residency by Investment');
                $meta['description'] = __('Secure EU residency through real estate in Greece. View qualifying portfolios from €250k.');
                break;
            case 'public.uk-investment':
                $meta['title'] = __('UK Property Investment - Buy-to-Let Yields');
                $meta['description'] = __('Premium UK investment properties in Manchester & London. High yield buy-to-let opportunities.');
                break;
            case 'public.calculator':
                $meta['title'] = __('Real Estate ROI Calculator - Global Investment Projection');
                $meta['description'] = __('Calculate your property returns and 5-year growth across TR, GR, and UK markets.');
                break;
        }

        return $meta;
    }

    /**
     * Generate HrefLang tags for all active languages.
     */
    private function getHrefLangs(): array
    {
        $localeService = app(\App\Services\LocaleControlService::class);
        $activeLangs = $localeService->getActiveLanguages();
        $currentRoute = Route::current();

        if (!$currentRoute) return [];

        $langs = [];
        foreach ($activeLangs as $lang) {
            // Re-generate URL with different locale if it's a prefixed route
            $langs[$lang->code] = $this->getLocalizedUrl($lang->code);
        }

        return $langs;
    }

    private function getLocalizedUrl(string $locale): string
    {
        $current = request();
        $segments = $current->segments();

        // If first segment is 2 chars, assume it's locale and replace it
        if (isset($segments[0]) && strlen($segments[0]) === 2) {
            $segments[0] = $locale;
        } else {
            // For non-prefixed routes, prefix it (if we want every page localized)
            array_unshift($segments, $locale);
        }

        return url(implode('/', $segments));
    }

    private function getOgLocale(string $locale): string
    {
        $locales = [
            'tr' => 'tr_TR',
            'en' => 'en_GB',
            'ru' => 'ru_RU',
            'ar' => 'ar_SA',
        ];

        return $locales[$locale] ?? 'en_US';
    }

    /**
     * Generate JSON-LD Schema.
     */
    private function getSchema(?string $routeName, string $locale): string
    {
        $base = [
            '@context' => 'https://schema.org',
            '@type'    => 'RealEstateAgent',
            'name'     => 'Yalıhan Emlak',
            'url'      => url('/'),
            'logo'     => asset('images/logo.png'),
        ];

        switch ($routeName) {
            case 'public.invest-in-turkey':
                $base['@type'] = 'InvestmentOrDeposit';
                $base['name'] = __('Invest in Turkey - Performance Real Estate');
                break;
            case 'public.calculator':
                $base['@type'] = 'WebApplication';
                $base['name'] = __('Real Estate ROI Calculator');
                break;
        }

        return json_encode($base, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
