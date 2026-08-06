<?php

namespace App\Domains\GuestCommunication\Services;

use App\Models\PropertyReservation;

/**
 * LanguageResolver
 *
 * GuestCommunication WAVE 1
 *
 * Rezervasyon'dan misafir dilini çıkarır.
 * Airbnb booking_country_code -> ISO dil kodu (TR/EN/AR)
 *
 * Kural:
 * - TR, AZ, GK, UK → TR
 * - DE, AT, CH, NL → EN (International)
 * - SA, AE, EG, KW, QA → AR
 * - Default → EN
 */
class LanguageResolver
{
    /**
     * Supported languages for WAVE 1
     */
    private const SUPPORTED_LANGUAGES = ['tr', 'en', 'ar'];

    /**
     * Country code to language mapping
     */
    private const COUNTRY_LANGUAGE_MAP = [
        // Turkish
        'TR' => 'tr',
        'AZ' => 'tr', // Azerbaijan - Turkish speakers
        'GK' => 'tr', // Cyprus - Turkish
        'UK' => 'tr', // Ukraine - some Turkish speakers

        // Arabic
        'SA' => 'ar', // Saudi Arabia
        'AE' => 'ar', // UAE
        'EG' => 'ar', // Egypt
        'KW' => 'ar', // Kuwait
        'QA' => 'ar', // Qatar
        'BH' => 'ar', // Bahrain
        'OM' => 'ar', // Oman
        'JO' => 'ar', // Jordan
        'LB' => 'ar', // Lebanon

        // International (English default)
        'DE' => 'en', // Germany
        'AT' => 'en', // Austria
        'CH' => 'en', // Switzerland
        'NL' => 'en', // Netherlands
        'FR' => 'en', // France
        'IT' => 'en', // Italy
        'ES' => 'en', // Spain
        'GB' => 'en', // United Kingdom
        'US' => 'en', // United States
        'RU' => 'en', // Russia
        'PL' => 'en', // Poland
        'SE' => 'en', // Sweden
        'NO' => 'en', // Norway
        'DK' => 'en', // Denmark
        'FI' => 'en', // Finland
        'IE' => 'en', // Ireland
        'BE' => 'en', // Belgium
        'GR' => 'en', // Greece
        'PT' => 'en', // Portugal
        'CZ' => 'en', // Czech Republic
        'HU' => 'en', // Hungary
        'RO' => 'en', // Romania
        'BG' => 'en', // Bulgaria
        'HR' => 'en', // Croatia
        'RS' => 'en', // Serbia
        'IL' => 'en', // Israel
        'BR' => 'en', // Brazil
        'AR' => 'en', // Argentina
        'MX' => 'en', // Mexico
        'AU' => 'en', // Australia
        'NZ' => 'en', // New Zealand
        'ZA' => 'en', // South Africa
        'IN' => 'en', // India
        'CN' => 'en', // China
        'JP' => 'en', // Japan
        'KR' => 'en', // South Korea
        'TH' => 'en', // Thailand
        'SG' => 'en', // Singapore
        'MY' => 'en', // Malaysia
        'ID' => 'en', // Indonesia
        'PH' => 'en', // Philippines
        'VN' => 'en', // Vietnam
    ];

    /**
     * Resolve language from reservation
     */
    public function resolveFromReservation(PropertyReservation $reservation): string
    {
        // Priority 1: Check explicit language field (if exists)
        if (!empty($reservation->preferred_language)) {
            return $this->normalizeLanguage($reservation->preferred_language);
        }

        // Priority 2: Check booking country code
        if (!empty($reservation->booking_country_code)) {
            return $this->resolveFromCountryCode($reservation->booking_country_code);
        }

        // Priority 3: Check tenant/ilan default language
        if ($reservation->ilan && !empty($reservation->ilan->default_language)) {
            return $this->normalizeLanguage($reservation->ilan->default_language);
        }

        // Priority 4: Fallback to English
        return 'en';
    }

    /**
     * Resolve language from country code
     */
    public function resolveFromCountryCode(string $countryCode): string
    {
        $code = strtoupper(trim($countryCode));

        if (isset(self::COUNTRY_LANGUAGE_MAP[$code])) {
            return self::COUNTRY_LANGUAGE_MAP[$code];
        }

        // Default fallback
        return 'en';
    }

    /**
     * Normalize language code to supported format
     */
    public function normalizeLanguage(string $language): string
    {
        $lang = strtolower(trim($language));

        // Handle common variations
        $aliases = [
            'turkish' => 'tr',
            'turkce' => 'tr',
            'tur' => 'tr',
            'türkçe' => 'tr',
            'english' => 'en',
            'eng' => 'en',
            'arabic' => 'ar',
            'ara' => 'ar',
            'arapça' => 'ar',
        ];

        if (isset($aliases[$lang])) {
            return $aliases[$lang];
        }

        // Validate against supported languages
        if (in_array($lang, self::SUPPORTED_LANGUAGES, true)) {
            return $lang;
        }

        // Default fallback
        return 'en';
    }

    /**
     * Check if language is supported
     */
    public function isSupported(string $language): bool
    {
        $lang = strtolower(trim($language));

        // Check if it's exactly a supported language
        if (in_array($lang, self::SUPPORTED_LANGUAGES, true)) {
            return true;
        }

        // Check aliases - if it maps to a supported language, return true
        $aliases = [
            'turkish' => 'tr', 'turkce' => 'tr', 'tur' => 'tr', 'türkçe' => 'tr',
            'english' => 'en', 'eng' => 'en',
            'arabic' => 'ar', 'ara' => 'ar', 'arapça' => 'ar',
        ];

        if (isset($aliases[$lang])) {
            return true;
        }

        return false;
    }

    /**
     * Get all supported languages
     */
    public function getSupportedLanguages(): array
    {
        return self::SUPPORTED_LANGUAGES;
    }

    /**
     * Get language display name
     */
    public function getDisplayName(string $language): string
    {
        $names = [
            'tr' => 'Türkçe',
            'en' => 'English',
            'ar' => 'العربية',
        ];

        return $names[$this->normalizeLanguage($language)] ?? $language;
    }
}
