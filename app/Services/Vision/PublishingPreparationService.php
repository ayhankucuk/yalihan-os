<?php

namespace App\Services\Vision;

use App\DTOs\Vision\PublishingMediaDTO;
use App\DTOs\Vision\VisionAnalysisDTO;
use App\Models\Ilan;
use App\Models\IlanFotografi;

/**
 * Publishing Preparation Service — Sprint 6.4
 *
 * AI Vision çıktısını publishing için hazırlanmış formata dönüştürür.
 * PUBLISH ETMEZ — sadece output üretir.
 *
 * Output:
 *   - Hero photo suggestion + reason
 *   - Photo ordering
 *   - Photo captions (title hints)
 *   - Room metadata per photo
 *   - Publishing readiness assessment
 */
class PublishingPreparationService
{
    /**
     * Bir ilan için publishing preparation output üretir.
     *
     * @param  Ilan  $ilan
     * @param  array<int, VisionAnalysisDTO>  $analyses  key = fotograf_id
     * @return PublishingMediaDTO
     */
    public function prepare(Ilan $ilan, array $analyses): PublishingMediaDTO
    {
        $fotograflar = $ilan->fotograflar()->orderBy('display_order')->get();

        if ($fotograflar->isEmpty()) {
            return $this->emptyResult($ilan->id);
        }

        // Hero selection
        $hero = $this->selectHero($analyses, $fotograflar);

        // Photo ordering
        $photoOrder = $this->buildPhotoOrder($analyses, $fotograflar);

        // Room metadata per photo
        $roomMetadata = $this->buildRoomMetadata($analyses);

        // Title hints
        $titleHints = $this->generateTitleHints($analyses, $ilan);

        // Captions
        $captions = $this->generateCaptions($analyses);

        // Readiness assessment
        $readiness = $this->assessReadiness($analyses, $fotograflar->count());

        // Aggregations
        $detectedRooms = $this->aggregateRooms($analyses);
        $detectedAmenities = $this->aggregateAmenities($analyses);
        $detectedLuxury = $this->aggregateLuxury($analyses);

        // Vision score
        $visionScore = $this->calcVisionScore($analyses);
        $avgConfidence = $this->calcAvgConfidence($analyses);

        return new PublishingMediaDTO(
            ilan_id: $ilan->id,
            hero_fotograf_id: $hero['fotograf_id'],
            hero_reason: $hero['reason'],
            photo_order: $photoOrder,
            title_hints: $titleHints,
            photo_captions: $captions,
            room_metadata: $roomMetadata,
            is_publishing_ready: $readiness['is_ready'],
            readiness_issues: $readiness['issues'],
            detected_rooms: $detectedRooms,
            detected_amenities: $detectedAmenities,
            detected_luxury_features: $detectedLuxury,
            vision_score: $visionScore,
            avg_ai_confidence: $avgConfidence,
        );
    }

    // ─── Private ───────────────────────────────────────────────────────────────

    private function selectHero(array $analyses, $fotograflar): array
    {
        $bestScore = -1;
        $bestId = null;
        $bestReason = '';

        foreach ($fotograflar as $foto) {
            $analysis = $analyses[$foto->id] ?? null;

            if ($analysis === null || $analysis->hasError()) {
                continue;
            }

            // Score = AI quality * confidence * room type multiplier
            $roomMultiplier = $this->heroRoomMultiplier($analysis->final_room_type);
            $score = $analysis->ai_quality_score * ($analysis->overall_confidence) * $roomMultiplier;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = $foto->id;
                $bestReason = $this->heroReason($analysis);
            }
        }

        // Fallback: en yüksek kaliteli fotoğraf
        if ($bestId === null) {
            foreach ($fotograflar as $foto) {
                if ($foto->kalite_puani > ($bestScore >= 0 ? $bestScore : 0)) {
                    $bestScore = $foto->kalite_puani ?? 0;
                    $bestId = $foto->id;
                    $bestReason = "Yüksek kalite skoru ({$bestScore}) ile seçildi.";
                }
            }
        }

        return [
            'fotograf_id' => $bestId,
            'reason'     => $bestReason,
        ];
    }

    private function heroRoomMultiplier(?string $roomType): float
    {
        // Exterior ve view öncelikli
        return match ($roomType) {
            'exterior', 'view'          => 1.5,
            'living_room', 'pool'       => 1.3,
            'garden', 'terrace'         => 1.2,
            'bedroom', 'kitchen'        => 1.0,
            default                      => 0.8,
        };
    }

    private function heroReason(VisionAnalysisDTO $analysis): string
    {
        $parts = [];

        if ($analysis->ai_quality_score >= 80) {
            $parts[] = "yüksek AI kalite skoru ({$analysis->ai_quality_score})";
        }

        $topRoom = $analysis->topRoom();
        if ($topRoom !== null) {
            $parts[] = "{$topRoom->label} tespit edildi";
        }

        if (!empty($analysis->luxuryFeatures)) {
            $parts[] = count($analysis->luxuryFeatures) . " lüks özellik";
        }

        return empty($parts) ? 'AI tarafından en yüksek skorlu fotoğraf olarak değerlendirildi.' : implode(', ', $parts);
    }

    /** @return int[] Sıralı fotograf_id dizisi */
    private function buildPhotoOrder(array $analyses, $fotograflar): array
    {
        // Önce hero, sonra diğerleri AI quality'ye göre
        $scored = [];

        foreach ($fotograflar as $foto) {
            $analysis = $analyses[$foto->id] ?? null;
            $score = $analysis?->ai_quality_score ?? ($foto->kalite_puani ?? 50);

            $scored[] = [
                'id'    => $foto->id,
                'score' => $score,
                'is_hero' => $analysis?->final_room_type === 'exterior' || $analysis?->final_room_type === 'view',
            ];
        }

        // Önce exterior/view (hero adayı), sonra quality sırasına göre
        usort($scored, fn($a, $b) => $b['is_hero'] <=> $a['is_hero'] ?: $b['score'] <=> $a['score']);

        return array_column($scored, 'id');
    }

    /** @return array<int, array> key = fotograf_id */
    private function buildRoomMetadata(array $analyses): array
    {
        $metadata = [];

        foreach ($analyses as $fotoId => $analysis) {
            if ($analysis->hasError()) {
                continue;
            }

            $rooms = [];
            foreach ($analysis->rooms as $room) {
                $rooms[] = [
                    'tip'      => $room->label,
                    'guven'    => $room->confidence,
                    'neden'    => $room->reason,
                ];
            }

            $metadata[$fotoId] = [
                'odalar'           => $rooms,
                'ameniteler'       => array_map(fn($a) => ['label' => $a->label, 'guven' => $a->confidence], $analysis->amenities),
                'lüks_ozellikler'  => array_map(fn($l) => ['label' => $l->label, 'guven' => $l->confidence], $analysis->luxuryFeatures),
                'manzaralar'       => array_map(fn($v) => ['label' => $v->label, 'guven' => $v->confidence], $analysis->views),
                'mimari_stil'      => $analysis->architecturalStyles[0]?->label,
                'ai_kalite'        => $analysis->ai_quality_score,
            ];
        }

        return $metadata;
    }

    /** @return string[] */
    private function generateTitleHints(array $analyses, Ilan $ilan): array
    {
        $hints = [];

        // En yaygın oda türünü bul
        $roomCounts = [];
        foreach ($analyses as $analysis) {
            $top = $analysis->topRoom();
            if ($top !== null) {
                $roomCounts[$top->label] = ($roomCounts[$top->label] ?? 0) + 1;
            }
        }

        arsort($roomCounts);
        $topRoom = array_key_first($roomCounts);

        if ($topRoom !== null) {
            $hints[] = "{$topRoom} manzaralı";
        }

        // Lüks özellik varsa
        $luxuryCount = 0;
        foreach ($analyses as $analysis) {
            $luxuryCount += count($analysis->luxuryFeatures);
        }

        if ($luxuryCount >= 3) {
            $hints[] = 'Lüks donanımlı';
        }

        // Location hint
        $hints[] = $ilan->il_adi ?? 'Bodrum';

        return array_unique($hints);
    }

    /** @return array<int, array{baslik: string, aciklama: string}> key = fotograf_id */
    private function generateCaptions(array $analyses): array
    {
        $captions = [];

        foreach ($analyses as $fotoId => $analysis) {
            if ($analysis->hasError()) {
                continue;
            }

            $topRoom = $analysis->topRoom();
            $roomLabel = $topRoom?->label ?? 'Mülk';

            $amenityLabels = array_slice(
                array_column(array_map(fn($a) => $a->toArray(), $analysis->amenities), 'label'),
                0, 3
            );

            $captionTitle = "{$roomLabel}" . (!empty($amenityLabels) ? ' — ' . implode(', ', $amenityLabels) : '');
            $captionDesc  = $analysis->overall_confidence >= 0.8
                ? "Bu fotoğraf AI tarafından yüksek güvenilirlikle analiz edilmiştir."
                : "Bu fotoğraf AI tarafından {$analysis->provider} ile analiz edilmiştir.";

            $captions[$fotoId] = [
                'baslik'    => $captionTitle,
                'aciklama'  => $captionDesc,
            ];
        }

        return $captions;
    }

    private function assessReadiness(array $analyses, int $totalPhotos): array
    {
        $issues = [];

        $analyzedCount = 0;
        $totalConfidence = 0.0;

        foreach ($analyses as $analysis) {
            if (!$analysis->hasError()) {
                $analyzedCount++;
                $totalConfidence += $analysis->overall_confidence;
            }
        }

        // Fotoğraf yok
        if ($totalPhotos === 0) {
            $issues[] = 'Fotoğraf yok';
        }

        // Çok az analiz
        if ($totalPhotos > 0 && $analyzedCount / $totalPhotos < 0.5) {
            $issues[] = 'Yeterli fotoğraf analiz edilemedi';
        }

        // Ortalama güven düşük
        if ($analyzedCount > 0) {
            $avgConf = $totalConfidence / $analyzedCount;
            if ($avgConf < 0.5) {
                $issues[] = 'Düşük AI güvenilirlik (ortalama < 50%)';
            }
        }

        return [
            'is_ready' => empty($issues),
            'issues'   => $issues,
        ];
    }

    /** @return array<string, int> */
    private function aggregateRooms(array $analyses): array
    {
        $counts = [];

        foreach ($analyses as $analysis) {
            $top = $analysis->topRoom();
            if ($top !== null) {
                $counts[$top->label] = ($counts[$top->label] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /** @return array<string> */
    private function aggregateAmenities(array $analyses): array
    {
        $items = [];

        foreach ($analyses as $analysis) {
            foreach ($analysis->amenities as $obj) {
                if ($obj->confidence >= 0.6) {
                    $items[$obj->label] = true;
                }
            }
        }

        return array_keys($items);
    }

    /** @return array<string> */
    private function aggregateLuxury(array $analyses): array
    {
        $items = [];

        foreach ($analyses as $analysis) {
            foreach ($analysis->luxuryFeatures as $obj) {
                if ($obj->confidence >= 0.6) {
                    $items[$obj->label] = true;
                }
            }
        }

        return array_keys($items);
    }

    private function calcVisionScore(array $analyses): int
    {
        if (empty($analyses)) {
            return 0;
        }

        $totalScore = 0;
        $validCount = 0;

        foreach ($analyses as $analysis) {
            if (!$analysis->hasError()) {
                $totalScore += $analysis->ai_quality_score;
                $validCount++;
            }
        }

        if ($validCount === 0) {
            return 0;
        }

        return (int) round($totalScore / $validCount);
    }

    private function calcAvgConfidence(array $analyses): float
    {
        if (empty($analyses)) {
            return 0.0;
        }

        $total = 0.0;
        $validCount = 0;

        foreach ($analyses as $analysis) {
            if (!$analysis->hasError()) {
                $total += $analysis->overall_confidence;
                $validCount++;
            }
        }

        return $validCount > 0 ? round($total / $validCount, 3) : 0.0;
    }

    private function emptyResult(int $ilanId): PublishingMediaDTO
    {
        return new PublishingMediaDTO(
            ilan_id: $ilanId,
            hero_fotograf_id: null,
            hero_reason: 'Fotoğraf yok',
            photo_order: [],
            readiness_issues: ['Fotoğraf bulunamadı'],
        );
    }
}
