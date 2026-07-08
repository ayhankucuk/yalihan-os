<?php

namespace App\Services\Media;

/**
 * Hero Image Selector — Sprint 6.3
 *
 * Hero Score = f(room_priority, sharpness, brightness, exposure, confidence)
 *
 * Formula:
 *   hero_score = (
 *     room_priority[oda_turu] * 0.30 +
 *     sharpness * 0.25 +
 *     brightness * 0.20 +
 *     exposure * 0.15 +
 *     oda_guven_skoru * 0.10
 *   )
 *
 * Room priority: Pool > View > LivingRoom > Bedroom > Kitchen > Bathroom > Terrace > Garden > Exterior > Other
 */
class HeroImageSelector
{
    /** @var array<string, float>  Oda türü öncelikleri (0.0–1.0) */
    private const ROOM_PRIORITY = [
        'pool' => 1.00,
        'view' => 0.95,
        'living_room' => 0.90,
        'bedroom' => 0.80,
        'kitchen' => 0.70,
        'bathroom' => 0.60,
        'terrace' => 0.55,
        'garden' => 0.50,
        'exterior' => 0.40,
        'other' => 0.20,
    ];

    /**
     * Hero fotoğrafı seç.
     *
     * @param  array<int, array{
     *     fotograf_id: int,
     *     oda_turu: string|null,
     *     oda_guven_skoru: int,
     *     kalite_ayrinti: array{sharpness: int, brightness: int, exposure: int},
     * }>  $photoAnalyses
     * @return array{hero_fotograf_id: int|null, hero_score: float}
     */
    public function select(array $photoAnalyses): array
    {
        $best = null;
        $bestScore = -1;

        foreach ($photoAnalyses as $photo) {
            $score = $this->calculateHeroScore($photo);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $photo['fotograf_id'];
            }
        }

        return [
            'hero_fotograf_id' => $best,
            'hero_score' => $bestScore >= 0 ? round($bestScore, 1) : 0.0,
        ];
    }

    /**
     * Tüm fotoğraflara hero score hesapla.
     *
     * @param  array<int, array{
     *     fotograf_id: int,
     *     oda_turu: string|null,
     *     oda_guven_skoru: int,
     *     kalite_ayrinti: array{sharpness: int, brightness: int, exposure: int},
     * }>  $photoAnalyses
     * @return array<int, float>  key = fotograf_id, value = hero_score
     */
    public function scoreAll(array $photoAnalyses): array
    {
        $scores = [];
        foreach ($photoAnalyses as $photo) {
            $scores[$photo['fotograf_id']] = round($this->calculateHeroScore($photo), 1);
        }
        return $scores;
    }

    /**
     * Hero score hesapla.
     */
    private function calculateHeroScore(array $photo): float
    {
        $odaTuru = $photo['oda_turu'] ?? 'other';
        $ayrinti = $photo['kalite_ayrinti'] ?? [];

        $roomPriority = self::ROOM_PRIORITY[$odaTuru] ?? self::ROOM_PRIORITY['other'];
        $sharpness = ($ayrinti['sharpness'] ?? 0) / 100;
        $brightness = ($ayrinti['brightness'] ?? 0) / 100;
        $exposure = ($ayrinti['exposure'] ?? 0) / 100;
        $confidence = ($photo['oda_guven_skoru'] ?? 0) / 100;

        return (
            $roomPriority * 0.30 +
            $sharpness * 0.25 +
            $brightness * 0.20 +
            $exposure * 0.15 +
            $confidence * 0.10
        ) * 100;
    }
}
