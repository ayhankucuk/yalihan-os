<?php

namespace App\Services\Seo;

use App\Models\Ilan;
use Illuminate\Support\Str;

class SeoEngineService
{
    /**
     * Generate deterministic SEO meta
     */
    public function generateSeoMeta(Ilan $ilan): array
    {
        $title = $this->generateTitle($ilan);
        $description = $this->generateDescription($ilan);
        $keywords = $this->generateKeywords($ilan);

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'og:title' => $title,
            'og:description' => $description,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function generateTitle(Ilan $ilan): string
    {
        // Format: [Kategori] [Yayin Tipi] - [Il] / [Ilce] - [m2] - [Fiyat]
        $parts = [];

        // Kategori + Yayin Tipi
        $kategori = $this->resolveAttribute($ilan, 'kategori', 'kategori_adi');
        $yayinTipi = $this->resolveAttribute($ilan, 'yayin_tipi', 'ad');

        if ($kategori && $yayinTipi) {
            $parts[] = $kategori . ' ' . $yayinTipi;
        } else {
            $parts[] = $ilan->baslik ?? '';
        }

        // Location (Il / Ilce)
        $il = $this->resolveAttribute($ilan, 'il', 'il_adi');
        $ilce = $this->resolveAttribute($ilan, 'ilce', 'ilce_adi');

        if ($il && $ilce) {
            $parts[] = "$il / $ilce";
        }

        // M2
        if ($ilan->alan_m2) {
            $parts[] = intval($ilan->alan_m2) . ' m²';
        }

        // Price
        if ($ilan->fiyat) {
            $parts[] = number_format($ilan->fiyat, 0, ',', '.') . ' ' . ($ilan->para_birimi ?? 'TRY');
        }

        return Str::limit(implode(' - ', array_filter($parts)), 100);
    }

    public function generateDescription(Ilan $ilan): string
    {
        // Format: [Aciklama summary]... [Fiyat]. Detaylar için tıklayın.
        // Total MUST be <= 160.

        $price = number_format($ilan->fiyat ?? 0, 0, ',', '.') . ' ' . ($ilan->para_birimi ?? 'TRY');
        $suffix = " Fiyat: $price. Detaylar için tıklayın.";
        $suffixLen = mb_strlen($suffix);

        // Very conservative buffer (20 chars) to handle multi-byte vs single-byte issues
        $availableLen = 160 - $suffixLen - 20;
        if ($availableLen < 10) $availableLen = 50;

        $desc = Str::limit(strip_tags($ilan->aciklama ?? ''), $availableLen);

        return $desc . $suffix;
    }

    private function resolveAttribute($model, $attribute, $relField)
    {
        // 1. Try raw attribute (matches test usage and import fields)
        $raw = $model->getAttribute($attribute);
        if (!empty($raw) && is_string($raw)) {
            return $raw;
        }

        // 2. Try relationship (matches production usage with IDs)
        // Accessing property via magic method to load relationship if needed
        $rel = $model->$attribute;
        if ($rel instanceof \Illuminate\Database\Eloquent\Model) {
            return $rel->$relField ?? null;
        }

        return null;
    }

    public function generateKeywords(Ilan $ilan): array
    {
        $keywords = [];
        // Extract from category, location, title
        if ($ilan->anaKategori) $keywords[] = $ilan->anaKategori->kategori_adi;
        if ($ilan->altKategori) $keywords[] = $ilan->altKategori->kategori_adi;
        if ($ilan->il) $keywords[] = $ilan->il->il_adi;
        if ($ilan->ilce) $keywords[] = $ilan->ilce->ilce_adi;
        if ($ilan->mahalle) $keywords[] = $ilan->mahalle->mahalle_adi;

        // Add words from title (basic tokenization)
        $titleWords = explode(' ', $ilan->baslik ?? '');
        $keywords = array_merge($keywords, array_slice($titleWords, 0, 5));

        return array_unique(array_filter($keywords, fn($k) => mb_strlen($k) > 3));
    }

    public function generateJsonLd(Ilan $ilan): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Offer',
            'name' => $ilan->baslik,
            'description' => $ilan->aciklama,
            'price' => $ilan->fiyat,
            'priceCurrency' => $ilan->para_birimi,
            'itemOffered' => [
                '@type' => 'RealEstateListing',
                'name' => $ilan->baslik,
            ],
            'seller' => [
                '@type' => 'RealEstateAgent',
                'name' => 'Yalıhan Emlak',
            ],
        ];
    }
}
