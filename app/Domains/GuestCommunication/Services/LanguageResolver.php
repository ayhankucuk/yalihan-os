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
     *
     * Fallback Order:
     * 1. guest_preferred_language (explicit guest preference) — nullable
     * 2. booking_locale (Airbnb/Booking locale) — nullable
     * 3. booking_country_code (ISO country code) — nullable
     * 4. listing_default_language (property default) — nullable
     * 5. English (fallback)
     *
     * @throws \InvalidArgumentException if reservation is null
     */
    public function resolveFromReservation(?PropertyReservation $reservation): string
    {
        if (!$reservation) {
            return 'en'; // Default fallback
        }

        // Priority 1: Check explicit guest preferred language (if column exists)
        if ($this->hasProperty($reservation, 'preferred_language')) {
            $lang = $this->safeGetProperty($reservation, 'preferred_language');
            if ($lang && $this->isSupported($this->normalizeLanguage($lang))) {
                return $this->normalizeLanguage($lang);
            }
        }

        // Priority 2: Check booking locale (Airbnb/Booking locale like "tr-TR", "en-US")
        if ($this->hasProperty($reservation, 'booking_locale')) {
            $locale = $this->safeGetProperty($reservation, 'booking_locale');
            if ($locale) {
                $localeLang = $this->extractLanguageFromLocale($locale);
                if ($localeLang) {
                    return $localeLang;
                }
            }
        }

        // Priority 3: Check booking country code (ISO alpha-2)
        if ($this->hasProperty($reservation, 'booking_country_code')) {
            $countryCode = $this->safeGetProperty($reservation, 'booking_country_code');
            if ($countryCode) {
                return $this->resolveFromCountryCode($countryCode);
            }
        }

        // Priority 4: Check tenant/ilan default language
        if ($reservation->ilan) {
            $lang = $this->safeGetProperty($reservation->ilan, 'default_language');
            if ($lang && $this->isSupported($this->normalizeLanguage($lang))) {
                return $this->normalizeLanguage($lang);
            }
        }

        // Priority 5: Fallback to English
        return 'en';
    }

    /**
     * Check if model has a property (column)
     */
    private function hasProperty($model, string $property): bool
    {
        return property_exists($model, $property);
    }

    /**
     * Safely get a property value
     */
    private function safeGetProperty($model, string $property): ?string
    {
        if (!property_exists($model, $property)) {
            return null;
        }

        $value = $model->$property ?? null;
        return is_string($value) && !empty(trim($value)) ? trim($value) : null;
    }

    /**
     * Extract language code from locale string (e.g., "tr-TR" -> "tr", "en-US" -> "en")
     */
    private function extractLanguageFromLocale(string $locale): ?string
    {
        // Handle formats like "tr-TR", "en_US", "ar-SA"
        $parts = preg_split('/[-_]/', $locale);

        if (!empty($parts[0])) {
            $lang = strtolower(trim($parts[0]));
            if ($this->isSupported($lang)) {
                return $lang;
            }
        }

        return null;
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
