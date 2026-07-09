<?php

namespace App\Services\Vision;

use App\DTOs\Vision\VisionAnalysisDTO;
use App\DTOs\Vision\PublishingMediaDTO;
use App\DTOs\Vision\VisionObjectDTO;
use App\Models\Ilan;
use App\Models\IlanFotografi;

/**
 * AI Metadata Extraction Service — Sprint 6.4
 *
 * AI Vision çıktısından yapılandırılmış metadata üretir.
 *
 * Extracts:
 *   - Room descriptions
 *   - Furniture
 *   - Amenities
 *   - Luxury features
 *   - View type
 *   - Architectural style
 *
 * Stores as JSON metadata.
 */
class MetadataExtractionService
{
    private const MIN_CONFIDENCE = 0.60; // Minimum güven eşiği

    /**
     * Tek bir fotoğraf için AI metadata çıkarır ve JSON olarak döner.
     */
    public function extract(VisionAnalysisDTO $analysis): array
    {
        if ($analysis->hasError()) {
            return ['error' => $analysis->error];
        }

        return [
            'oda_bilgisi'    => $this->extractRoomInfo($analysis->rooms),
            'mobilya'       => $this->extractByType($analysis->furniture),
            'ameniteler'     => $this->extractByType($analysis->amenities),
            'lüks_ozellikler' => $this->extractByType($analysis->luxuryFeatures),
            'manzaralar'     => $this->extractByType($analysis->views),
            'mimari_stil'   => $this->extractStyle($analysis->architecturalStyles),
            'ai_kalite'     => [
                'toplam_puan'  => $analysis->ai_quality_score,
                'bilesenler'   => $analysis->ai_quality_breakdown,
            ],
            'guvenilirlik'  => [
                'genel'        => $analysis->overall_confidence,
                'füzyon'       => $analysis->fusion_confidence,
                'kaynak'       => $analysis->provider,
            ],
            'parsed_at'     => now()->toIso8601String(),
        ];
    }

    /**
     * Bir ilandaki tüm fotoğraflar için metadata'yı aggregate eder.
     *
     * @param  Ilan  $ilan
     * @param  array<int, VisionAnalysisDTO>  $analyses  key = fotograf_id
     * @return array{
     *     oda_dagilimi: array<string, int>,
     *     ameniteler: array<string, float>,
     *     lüks_ozellikler: array<string, float>,
     *     mimari_stiller: array<string, float>,
     *     manzaralar: array<string, float>,
     *     toplam_fotograf: int,
     *     analiz_edilen: int,
     *     ortalama_ai_guven: float,
     *     ortalama_kalite: float,
     * }
     */
    public function aggregateForIlan(Ilan $ilan, array $analyses): array
    {
        $roomDist     = [];
        $amenityScores = [];
        $luxuryScores  = [];
        $styleScores   = [];
        $viewScores    = [];
        $totalConfidence = 0.0;
        $totalQuality = 0;
        $analyzed = 0;

        foreach ($analyses as $fotoId => $analysis) {
            if ($analysis->hasError()) {
                continue;
            }

            $analyzed++;

            // Oda dağılımı
            $topRoom = $analysis->topRoom();
            if ($topRoom !== null) {
                $key = $topRoom->label;
                $roomDist[$key] = ($roomDist[$key] ?? 0) + 1;
            }

            // Amenity scores
            foreach ($analysis->amenities as $obj) {
                if ($obj->confidence >= self::MIN_CONFIDENCE) {
                    $slug = $obj->label;
                    if (!isset($amenityScores[$slug]) || $amenityScores[$slug] < $obj->confidence) {
                        $amenityScores[$slug] = $obj->confidence;
                    }
                }
            }

            // Luxury features
            foreach ($analysis->luxuryFeatures as $obj) {
                if ($obj->confidence >= self::MIN_CONFIDENCE) {
                    $slug = $obj->label;
                    if (!isset($luxuryScores[$slug]) || $luxuryScores[$slug] < $obj->confidence) {
                        $luxuryScores[$slug] = $obj->confidence;
                    }
                }
            }

            // Architectural styles
            foreach ($analysis->architecturalStyles as $obj) {
                if ($obj->confidence >= self::MIN_CONFIDENCE) {
                    $slug = $obj->label;
                    if (!isset($styleScores[$slug]) || $styleScores[$slug] < $obj->confidence) {
                        $styleScores[$slug] = $obj->confidence;
                    }
                }
            }

            // Views
            foreach ($analysis->views as $obj) {
                if ($obj->confidence >= self::MIN_CONFIDENCE) {
                    $slug = $obj->label;
                    if (!isset($viewScores[$slug]) || $viewScores[$slug] < $obj->confidence) {
                        $viewScores[$slug] = $obj->confidence;
                    }
                }
            }

            $totalConfidence += $analysis->overall_confidence;
            $totalQuality += $analysis->ai_quality_score;
        }

        return [
            'oda_dagilimi'    => $roomDist,
            'ameniteler'     => $amenityScores,
            'lüks_ozellikler' => $luxuryScores,
            'mimari_stiller' => $styleScores,
            'manzaralar'     => $viewScores,
            'toplam_fotograf' => count($analyses),
            'analiz_edilen'  => $analyzed,
            'ortalama_ai_guven' => $analyzed > 0 ? round($totalConfidence / $analyzed, 3) : 0.0,
            'ortalama_kalite' => $analyzed > 0 ? (int) round($totalQuality / $analyzed) : 0,
        ];
    }

    // ─── Private ───────────────────────────────────────────────────────────────

    /** @return array<string, mixed>|null */
    private function extractRoomInfo(array $rooms): ?array
    {
        $top = $rooms[0] ?? null;
        if ($top === null) {
            return null;
        }

        return [
            'tip'      => $top->label,
            'guven'    => $top->confidence,
            'kaynak'   => $top->provider,
            'neden'    => $top->reason,
        ];
    }

    /** @return array<int, array{label: string, confidence: float}> */
    private function extractByType(array $objects): array
    {
        $result = [];

        foreach ($objects as $obj) {
            if ($obj->confidence >= self::MIN_CONFIDENCE) {
                $result[] = [
                    'label'      => $obj->label,
                    'guven'      => round($obj->confidence, 3),
                    'neden'      => $obj->reason,
                    'meta'       => $obj->metadata,
                ];
            }
        }

        // Güven sırasına göre sırala
        usort($result, fn($a, $b) => $b['guven'] <=> $a['guven']);

        return $result;
    }

    /** @return array<string, mixed>|null */
    private function extractStyle(array $styles): ?array
    {
        $top = $styles[0] ?? null;
        if ($top === null) {
            return null;
        }

        return [
            'tip'    => $top->label,
            'guven'  => $top->confidence,
            'neden'  => $top->reason,
        ];
    }
}
