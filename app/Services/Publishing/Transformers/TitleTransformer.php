<?php

namespace App\Services\Publishing\Transformers;

use App\Models\Ilan;

/**
 * Title Transformer — Sprint 6.5
 *
 * AI Vision title_hints + Ilan başlığını kanal formatına dönüştürür.
 *
 * @rule sadece dönüştürür — iş mantığı PublishingIntelligenceOrchestrator'da.
 */
class TitleTransformer
{
    private const AIRBNB_MAX = 50;
    private const SAHINDEN_MAX = 80;
    private const HEPSIEMLAK_MAX = 100;

    /**
     * Airbnb başlık formatı: max 50 karakter.
     */
    public function forAirbnb(Ilan $ilan, array $visionHints = []): string
    {
        $parts = $this->buildParts($ilan, $visionHints);

        $title = implode(' · ', $parts);

        return mb_strlen($title) > self::AIRBNB_MAX
            ? mb_substr($title, 0, self::AIRBNB_MAX - 1) . '…'
            : $title;
    }

    /**
     * Sahibinden başlık formatı: max 80 karakter.
     */
    public function forSahibinden(Ilan $ilan, array $visionHints = []): string
    {
        $ilAdi = $ilan->il && isset($ilan->il->il_adi) ? $ilan->il->il_adi : ($ilan->il ?? '');
        $ilceAdi = $ilan->ilce && isset($ilan->ilce->ilce_adi) ? $ilan->ilce->ilce_adi : ($ilan->ilce ?? '');
        $location = trim($ilAdi . ' ' . $ilceAdi);

        $kategori = $ilan->altKategori && isset($ilan->altKategori->adi)
            ? $ilan->altKategori->adi
            : '';

        $hint = $visionHints[0] ?? '';

        $title = trim($hint . ' ' . $kategori . ' ' . $location);

        return mb_strlen($title) > self::SAHINDEN_MAX
            ? mb_substr($title, 0, self::SAHINDEN_MAX - 1) . '…'
            : $title;
    }

    /**
     * Hepsiemlak başlık formatı: max 100 karakter.
     */
    public function forHepsiemlak(Ilan $ilan, array $visionHints = []): string
    {
        $parts = $this->buildParts($ilan, $visionHints, true);

        $title = implode(' · ', $parts);

        return mb_strlen($title) > self::HEPSIEMLAK_MAX
            ? mb_substr($title, 0, self::HEPSIEMLAK_MAX - 1) . '…'
            : $title;
    }

    /**
     * AI Vision hints'dan öne çıkan kelime/grupları döner.
     *
     * @return string[]
     */
    public function extractHints(array $visionHints): array
    {
        return array_values(array_filter(
            array_map(fn($h) => is_string($h) ? trim($h) : '', $visionHints),
            fn($h) => !empty($h)
        ));
    }

    // ─── Private ───────────────────────────────────────────────────────────────

    /** @return string[] */
    private function buildParts(Ilan $ilan, array $visionHints, bool $large = false): array
    {
        $parts = [];

        // AI Vision hints (öncelikli)
        foreach (array_slice($visionHints, 0, $large ? 3 : 2) as $hint) {
            if (!empty(trim($hint))) {
                $parts[] = trim($hint);
            }
        }

        // Konum
        $ilAdi = $ilan->il && isset($ilan->il->il_adi) ? $ilan->il->il_adi : ($ilan->il ?? '');
        $ilceAdi = $ilan->ilce && isset($ilan->ilce->ilce_adi)
            ? ', ' . $ilan->ilce->ilce_adi
            : ($ilan->ilce ? ', ' . $ilan->ilce : '');
        $location = trim($ilAdi . $ilceAdi);
        if ($location) {
            $parts[] = $location;
        }

        // Kategori / tip
        $kategori = $ilan->altKategori && isset($ilan->altKategori->adi)
            ? $ilan->altKategori->adi
            : '';
        if ($kategori) {
            $parts[] = $kategori;
        }

        return array_values(array_filter($parts));
    }
}
