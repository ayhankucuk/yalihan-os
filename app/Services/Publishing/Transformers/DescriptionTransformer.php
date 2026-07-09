<?php

namespace App\Services\Publishing\Transformers;

use App\Models\Ilan;
use App\DTOs\Vision\PublishingMediaDTO;

/**
 * Description Transformer — Sprint 6.5
 *
 * AI Vision description parçacıklarını kanal formatına dönüştürür.
 * AI description generation yapmaz — sadece mevcut veriyi dönüştürür.
 *
 * @rule Sadece transform yapar — iş mantığı PublishingIntelligenceOrchestrator'da.
 */
class DescriptionTransformer
{
    /**
     * Airbnb formatında detaylı açıklama üretir.
     * Airbnb: summary (max 500) + description (detaylı).
     */
    public function forAirbnb(Ilan $ilan, ?PublishingMediaDTO $media = null): array
    {
        $summary = $this->buildAirbnbSummary($ilan, $media);
        $description = $this->buildAirbnbDescription($ilan, $media);
        $space = $this->buildAirbnbSpace($ilan, $media);
        $neighborhood = $this->buildAirbnbNeighborhood($ilan, $media);
        $access = $this->buildAirbnbAccess($ilan, $media);
        $interaction = $this->buildAirbnbInteraction($ilan, $media);
        $houseRules = $ilan->yorum ?? null;

        return [
            'summary' => $summary,
            'description' => $description,
            'space' => $space,
            'neighborhood_overview' => $neighborhood,
            'access' => $access,
            'interaction' => $interaction,
            'house_rules' => $houseRules,
        ];
    }

    /**
     * Sahibinden formatında açıklama üretir.
     * Sahibinden: tek parça açıklama, max ~4000 karakter.
     */
    public function forSahibinden(Ilan $ilan, ?PublishingMediaDTO $media = null): array
    {
        $parts = [];

        // AI Vision detayları
        if ($media && !empty($media->detected_amenities)) {
            $amenitiesList = implode(', ', array_slice($media->detected_amenities, 0, 10));
            $parts[] = "Emlak özellikleri: {$amenitiesList}.";
        }

        // Açıklama (varsa)
        if ($ilan->aciklama) {
            $parts[] = $ilan->aciklama;
        }

        // Oda bilgisi
        if ($media && !empty($media->detected_rooms)) {
            $rooms = implode(', ', $media->detected_rooms);
            $parts[] = "Tespit edilen alanlar: {$rooms}.";
        }

        $aciklama = implode("\n\n", array_filter($parts));
        $baslik = $ilan->baslik ?? '';

        return [
            'baslik' => $baslik,
            'aciklama' => $aciklama,
        ];
    }

    /**
     * Hepsiemlak formatında açıklama üretir.
     * Hepsiemlak: başlık + açıklama, max ~3000 karakter.
     */
    public function forHepsiemlak(Ilan $ilan, ?PublishingMediaDTO $media = null): array
    {
        $parts = [];

        if ($ilan->aciklama) {
            $parts[] = $ilan->aciklama;
        }

        // AI Vision luxury features
        if ($media && !empty($media->detected_luxury_features)) {
            $luxury = implode(', ', array_slice($media->detected_luxury_features, 0, 5));
            $parts[] = "Öne çıkan özellikler: {$luxury}.";
        }

        $aciklama = implode("\n\n", array_filter($parts));

        return [
            'baslik' => $ilan->baslik ?? '',
            'aciklama' => $aciklama,
        ];
    }

    // ─── Private — Airbnb helpers ───────────────────────────────────────────

    private function buildAirbnbSummary(Ilan $ilan, ?PublishingMediaDTO $media): string
    {
        $location = trim(($ilan->il?->il_adi ?? $ilan->il ?? '') . ', Bodrum');
        $category = $ilan->altKategori?->adi ?? 'Emlak';
        $hint = $media?->title_hints[0] ?? null;

        $parts = [];
        if ($hint) {
            $parts[] = $hint;
        }
        $parts[] = "{$category} — {$location}";

        $summary = implode('. ', $parts);

        return mb_strlen($summary) > 500
            ? mb_substr($summary, 0, 497) . '…'
            : $summary;
    }

    private function buildAirbnbDescription(Ilan $ilan, ?PublishingMediaDTO $media): string
    {
        $parts = [];

        if ($ilan->aciklama) {
            $parts[] = $ilan->aciklama;
        }

        // AI Vision photo captions birleştir
        if ($media && !empty($media->photo_captions)) {
            $captions = [];
            foreach (array_slice($media->photo_captions, 0, 5) as $caption) {
                if (!empty($caption['aciklama'])) {
                    $captions[] = $caption['aciklama'];
                }
            }
            if (!empty($captions)) {
                $parts[] = implode(' ', $captions);
            }
        }

        return implode("\n\n", array_filter($parts));
    }

    private function buildAirbnbSpace(Ilan $ilan, ?PublishingMediaDTO $media): ?string
    {
        if (!$media || (empty($media->detected_rooms) && empty($media->detected_amenities))) {
            return null;
        }

        $parts = [];

        if (!empty($media->detected_rooms)) {
            $rooms = implode(', ', $media->detected_rooms);
            $parts[] = "Tespit edilen alanlar: {$rooms}.";
        }

        if (!empty($media->detected_amenities)) {
            $amenities = implode(', ', array_slice($media->detected_amenities, 0, 8));
            $parts[] = "Özellikler: {$amenities}.";
        }

        return implode(' ', $parts);
    }

    private function buildAirbnbNeighborhood(Ilan $ilan, ?PublishingMediaDTO $media): ?string
    {
        $location = $ilan->il?->il_adi ?? $ilan->il ?? null;
        if (!$location) {
            return null;
        }

        return "Bodrum'un {$location} bölgesinde, sahile ve merkeze yakın konumda.";
    }

    private function buildAirbnbAccess(Ilan $ilan, ?PublishingMediaDTO $media): ?string
    {
        return null; // Mevcut verilerden üretilemez — manuel girdi gerekli
    }

    private function buildAirbnbInteraction(Ilan $ilan, ?PublishingMediaDTO $media): ?string
    {
        return null; // Mevcut verilerden üretilemez — manuel girdi gerekli
    }
}
