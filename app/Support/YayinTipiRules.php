<?php

namespace App\Support;

use InvalidArgumentException;

class YayinTipiRules
{
    public static function normalizeSlug(string $slug): string
    {
        $s = trim(strtolower($slug));
        $s = str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç'], ['i', 'g', 'u', 's', 'o', 'c'], $s);
        $s = str_replace([' ', '_'], '-', $s);
        $s = preg_replace('/[^a-z0-9\-]/', '', $s);

        return $s;
    }

    /**
     * Canonicalize any raw or legacy slug/value into a single canonical slug.
     *
     * This function is the SSOT for publication type slugs across:
     * - API (CategoriesController)
     * - Wizard / AI payloads
     * - Publish Gate
     * - Calendar / reservation guards
     */
    public static function canonicalizeSlug(string $raw): string
    {
        $normalized = self::normalizeSlug($raw);

        $map = [
            // Satılık / Kiralık / Devren
            'satilik' => 'satilik',
            'kiralik' => 'kiralik',
            'devren' => 'devren',
            'devren-satilik' => 'devren',
            'devren-kiralik' => 'devren',
            'kat-karsiligi' => 'kat-karsiligi',
            'kat-karsiligi-satis' => 'kat-karsiligi',

            // Konut Family Composite Slugs
            'konut-satilik' => 'satilik',
            'konut-kiralik' => 'kiralik',
            'villa-satilik' => 'satilik',
            'villa-kiralik' => 'kiralik',
            'villa-gunluk' => 'gunluk',
            'villa-haftalik' => 'haftalik',
            'villa-aylik' => 'aylik',
            'villa-sezonluk' => 'sezonluk',
            'daire-satilik' => 'satilik',
            'daire-kiralik' => 'kiralik',
            'mustakil-ev-satilik' => 'satilik',
            'mustakil-ev-kiralik' => 'kiralik',
            'dubleks-satilik' => 'satilik',
            'dubleks-kiralik' => 'kiralik',

            // İşyeri Family Composite Slugs
            'isyeri-satilik' => 'satilik',
            'isyeri-kiralik' => 'kiralik',
            'isyeri-devren' => 'devren',
            'ofis-satilik' => 'satilik',
            'ofis-kiralik' => 'kiralik',
            'ofis-devren' => 'devren',
            'dukkan-satilik' => 'satilik',
            'dukkan-kiralik' => 'kiralik',
            'dukkan-devren' => 'devren',
            'fabrika-satilik' => 'satilik',
            'fabrika-kiralik' => 'kiralik',
            'fabrika-devren' => 'devren',
            'depo-satilik' => 'satilik',
            'depo-kiralik' => 'kiralik',

            // Yazlık Kiralama Family Composite Slugs
            'yazlik-kiralama-gunluk' => 'gunluk',
            'yazlik-kiralama-haftalik' => 'haftalik',
            'yazlik-kiralama-aylik' => 'aylik',
            'yazlik-kiralama-sezonluk' => 'sezonluk',
            'villa-tipi-satilik' => 'satilik',
            'villa-tipi-gunluk' => 'gunluk',
            'villa-tipi-haftalik' => 'haftalik',
            'villa-tipi-aylik' => 'aylik',
            'villa-tipi-sezonluk' => 'sezonluk',
            'rezidans-tipi-satilik' => 'satilik',
            'rezidans-tipi-gunluk' => 'gunluk',
            'rezidans-tipi-haftalik' => 'haftalik',
            'rezidans-tipi-aylik' => 'aylik',
            'rezidans-tipi-sezonluk' => 'sezonluk',
            'daire-tipi-satilik' => 'satilik',
            'daire-tipi-gunluk' => 'gunluk',
            'daire-tipi-haftalik' => 'haftalik',
            'daire-tipi-aylik' => 'aylik',
            'daire-tipi-sezonluk' => 'sezonluk',
            'tas-ev-tipi-satilik' => 'satilik',
            'tas-ev-tipi-gunluk' => 'gunluk',
            'tas-ev-tipi-haftalik' => 'haftalik',
            'tas-ev-tipi-aylik' => 'aylik',
            'tas-ev-tipi-sezonluk' => 'sezonluk',
            'malikane-tipi-satilik' => 'satilik',
            'malikane-tipi-gunluk' => 'gunluk',
            'malikane-tipi-haftalik' => 'haftalik',
            'malikane-tipi-aylik' => 'aylik',
            'malikane-tipi-sezonluk' => 'sezonluk',
            'minimal-tipi-satilik' => 'satilik',
            'minimal-tipi-gunluk' => 'gunluk',
            'minimal-tipi-haftalik' => 'haftalik',
            'minimal-tipi-aylik' => 'aylik',
            'minimal-tipi-sezonluk' => 'sezonluk',

            // Turistik Tesisler Family Composite Slugs
            'turistik-tesisler-satilik' => 'satilik',
            'turistik-tesisler-kiralik' => 'kiralik',
            'otel-satilik' => 'satilik',
            'otel-kiralik' => 'kiralik',
            'pansiyon-satilik' => 'satilik',
            'pansiyon-kiralik' => 'kiralik',
            'tatil-koyu-satilik' => 'satilik',
            'tatil-koyu-kiralik' => 'kiralik',

            // Seasonal Rental Types (standalone)
            'gunluk' => 'gunluk',
            'gunluk-kiralik' => 'gunluk',
            'gunluk-kiralama' => 'gunluk',
            'haftalik' => 'haftalik',
            'haftalik-kiralik' => 'haftalik',
            'haftalik-kiralama' => 'haftalik',
            'aylik' => 'aylik',
            'aylik-kiralik' => 'aylik',
            'aylik-kiralama' => 'aylik',
            'sezonluk' => 'sezonluk',
            'sezonluk-kiralik' => 'sezonluk',
            'sezonluk-kiralama' => 'sezonluk',

            // Legacy Support
            'yazlik' => 'sezonluk',
            'yazlik-kiralik' => 'sezonluk',
            'yazlik-satilik' => 'satilik',

            // Arsa Family Composite Slugs
            'arsa-arazi-satilik' => 'satilik',
            'arsa-arazi-kiralik' => 'kiralik',
            'arsa-konut-villa-satilik' => 'satilik',
            'arsa-konut-villa-kiralik' => 'kiralik',
            'arsa-konut-villa-kat-karsiligi' => 'kat-karsiligi',
            'sanayi-ticari-imar-satilik' => 'satilik',
            'sanayi-ticari-imar-kiralik' => 'kiralik',
            'tarla-satilik' => 'satilik',
            'tarla-kiralik' => 'kiralik',
            'zeytinlik-satilik' => 'satilik',
            'bag-bahce-satilik' => 'satilik',
            'zeytinli-tarla-satilik' => 'satilik',
            'zeytinli-tarla-kiralik' => 'kiralik',
            'turizm-otel-kamp-satilik' => 'satilik',
            'turizm-otel-kamp-kiralik' => 'kiralik',
            'turizm-konut-satilik' => 'satilik',
            'turizm-konut-kiralik' => 'kiralik',

            // Proje Family Composite Slugs
            'projeden-satis-satilik' => 'satilik',
            'konut-projesi-satilik' => 'satilik',
            'villa-projesi-satilik' => 'satilik',
            'karma-proje-satilik' => 'satilik',
        ];

        $canonical = $map[$normalized] ?? $normalized;

        $known = [
            'satilik',
            'kiralik',
            'devren',
            'kat-karsiligi',
            'gunluk',
            'haftalik',
            'aylik',
            'sezonluk',  // Phase 6.7: Unified seasonal rental (replaces yazlik-kiralik)
        ];

        if (!in_array($canonical, $known, true)) {
            throw new InvalidArgumentException('Bilinmeyen yayın tipi: ' . $raw);
        }

        return $canonical;
    }

    public static function requiresCalendar(string $yayinTipiSlug): bool
    {
        $slug = self::canonicalizeSlug($yayinTipiSlug);
        $calendarSlugs = [
            'gunluk',
            'haftalik',
            'aylik',
            'sezonluk',  // Phase 6.7: Unified seasonal rental
        ];

        return in_array($slug, $calendarSlugs, true);
    }

    public static function supportsReservations(string $yayinTipiSlug): bool
    {
        return self::requiresCalendar($yayinTipiSlug);
    }

    public static function supportsPOI(string $yayinTipiSlug): bool
    {
        $slug = self::canonicalizeSlug($yayinTipiSlug);

        if ($slug === 'satilik' || $slug === 'devren') {
            return false;
        }

        return true;
    }

    public static function allowedForCalendarClose(string $yayinTipiSlug): bool
    {
        return self::requiresCalendar($yayinTipiSlug);
    }

    public static function guardKnown(string $yayinTipiSlug): void
    {
        // canonicalizeSlug will throw InvalidArgumentException if unknown
        self::canonicalizeSlug($yayinTipiSlug);
    }
}
