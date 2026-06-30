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

            // Günlük kiralama
            'gunluk' => 'gunluk',
            'gunluk-kiralik' => 'gunluk',
            'gunluk-kiralama' => 'gunluk',

            // Haftalık kiralama
            'haftalik' => 'haftalik',
            'haftalik-kiralik' => 'haftalik',
            'haftalik-kiralama' => 'haftalik',

            // Aylık kiralama
            'aylik' => 'aylik',
            'aylik-kiralik' => 'aylik',
            'aylik-kiralama' => 'aylik',

            // Sezonluk kiralama
            'sezonluk' => 'sezonluk',
            'sezonluk-kiralik' => 'sezonluk',
            'sezonluk-kiralama' => 'sezonluk',

            // Phase 6.7: Yazlık → Sezonluk migration (legacy support)
            'yazlik' => 'sezonluk',
            'yazlik-kiralik' => 'sezonluk',
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
