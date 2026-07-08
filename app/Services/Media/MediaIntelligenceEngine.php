<?php

namespace App\Services\Media;

use App\DTOs\Media\MediaAnalysisDTO;
use App\DTOs\Media\MediaPhotoDTO;
use App\DTOs\Media\MediaRoomDTO;
use App\Events\Media\HeroImageSelected;
use App\Events\Media\MediaAnalyzed;
use App\Events\Media\MediaHealthUpdated;
use App\Models\Ilan;
use App\Models\IlanFotografi;
use App\Services\Ilan\IlanPhotoService;
use Illuminate\Support\Facades\Log;

/**
 * Media Intelligence Orchestrator — Sprint 6.3
 *
 * Tüm medya zeka pipeline'ını koordine eder.
 *
 * Pipeline:
 *   1. Room Detection (RoomDetectionService)
 *   2. Quality Analysis (ImageQualityEngine)
 *   3. Coverage Analysis (CoverageAnalyzer)
 *   4. Hero Selection (HeroImageSelector)
 *   5. Media Health Score
 *   6. Persist + Events
 *
 * SAB Authority: Tek write authority — model yazma zincirine uyar.
 */
class MediaIntelligenceEngine
{
    public function __construct(
        private readonly RoomDetectionService $roomDetection,
        private readonly ImageQualityEngine $qualityEngine,
        private readonly CoverageAnalyzer $coverageAnalyzer,
        private readonly HeroImageSelector $heroSelector,
    ) {}

    /**
     * Bir ilanın tüm fotoğraflarını analiz et.
     *
     * @param  int       $ilanId
     * @param  bool      $dispatchEvents  Event fırlatılsın mı (default: true)
     * @return MediaAnalysisDTO
     */
    public function analyze(int $ilanId, bool $dispatchEvents = true): MediaAnalysisDTO
    {
        $ilan = Ilan::with('fotograflar')->findOrFail($ilanId);
        $fotograflar = $ilan->fotograflar;

        if ($fotograflar->isEmpty()) {
            return $this->emptyResult($ilanId);
        }

        // Step 1: Room Detection
        $roomResults = $this->roomDetection->detectBatch($fotograflar->all());

        // Step 2: Quality Analysis
        $qualityResults = $this->qualityEngine->analyzeBatch($fotograflar->all());

        // Step 3: Merge results → MediaPhotoDTO[]
        $photoDTOs = $this->buildPhotoDTOs($fotograflar, $roomResults, $qualityResults);

        // Step 4: Hero Selection
        $heroData = $this->heroSelector->select(array_map(
            fn($dto) => [
                'fotograf_id' => $dto->fotograf_id,
                'oda_turu' => $dto->oda_turu,
                'oda_guven_skoru' => $dto->oda_guven_skoru,
                'kalite_ayrinti' => $dto->kalite_ayrinti,
            ],
            $photoDTOs,
        ));

        // Step 5: Coverage Analysis
        $roomDTOs = $this->buildRoomDTOs($roomResults);
        $coverageResult = $this->coverageAnalyzer->analyze($roomDTOs);

        // Step 6: Media Health Score
        $qualityScores = array_column(array_map(fn($p) => $p->toArray(), $photoDTOs), 'kalite_puani');
        $avgQuality = count($qualityScores) > 0 ? array_sum($qualityScores) / count($qualityScores) : 0;
        $qualityScoreInt = (int) $avgQuality;
        $coverageScoreInt = (int) round($coverageResult['coverage'] * 100);

        $targetPhotos = min(count($photoDTOs), 10);
        $completenessBonus = min(100, (count($photoDTOs) / $targetPhotos) * 100);

        $healthScore = (int) min(100, round(
            $qualityScoreInt * 0.30 +
            $coverageScoreInt * 0.40 +
            $completenessBonus * 0.30,
        ));

        // Step 7: Persist to DB
        $this->persist($ilan, $photoDTOs, $heroData, $coverageResult, $healthScore, $qualityScoreInt);

        // Step 8: Events
        if ($dispatchEvents) {
            $this->dispatchEvents($ilanId, $healthScore, $qualityScoreInt, count($photoDTOs), $heroData['hero_fotograf_id'], $coverageResult['missing_rooms']);
        }

        return new MediaAnalysisDTO(
            ilan_id: $ilanId,
            toplam_fotograf: count($photoDTOs),
            media_health_score: $healthScore,
            media_quality_score: $qualityScoreInt,
            tamamlanma_oran: min(1.0, count($photoDTOs) / $targetPhotos),
            oda_detaylari: $roomDTOs,
            eksik_odalar: $coverageResult['missing_rooms'],
            hero_fotograf_id: $heroData['hero_fotograf_id'],
            tum_fotograflar: $photoDTOs,
        );
    }

    /**
     * MediaPhotoDTO[] oluştur.
     *
     * @return MediaPhotoDTO[]
     */
    private function buildPhotoDTOs(
        $fotograflar,
        array $roomResults,
        array $qualityResults,
    ): array {
        $dtos = [];

        foreach ($fotograflar as $fotograf) {
            $room = $roomResults[$fotograf->id] ?? [
                'oda_turu' => 'other',
                'label' => 'Diğer',
                'guven_skoru' => 0,
            ];

            $quality = $qualityResults[$fotograf->id] ?? [
                'blur_score' => 0,
                'brightness' => 0,
                'exposure' => 0,
                'sharpness' => 0,
                'resolution' => 0,
                'quality_score' => 0,
            ];

            $dtos[] = new MediaPhotoDTO(
                fotograf_id: $fotograf->id,
                oda_turu: $room['oda_turu'],
                oda_guven_skoru: (int) ($room['guven_skoru'] ?? 0),
                kalite_puani: (int) ($quality['quality_score'] ?? 0),
                hero_skoru: 0.0,
                kalite_ayrinti: [
                    'blur_score' => (int) ($quality['blur_score'] ?? 0),
                    'brightness' => (int) ($quality['brightness'] ?? 0),
                    'exposure' => (int) ($quality['exposure'] ?? 0),
                    'sharpness' => (int) ($quality['sharpness'] ?? 0),
                    'resolution' => (int) ($quality['resolution'] ?? 0),
                ],
            );
        }

        return $dtos;
    }

    /**
     * MediaRoomDTO[] oluştur.
     *
     * @return MediaRoomDTO[]
     */
    private function buildRoomDTOs(array $roomResults): array
    {
        $byType = [];
        foreach ($roomResults as $fotoId => $result) {
            $turu = $result['oda_turu'];
            if (!isset($byType[$turu])) {
                $byType[$turu] = [
                    'label' => $result['label'],
                    'ids' => [],
                ];
            }
            $byType[$turu]['ids'][] = (int) $fotoId;
        }

        $dtos = [];
        foreach ($byType as $turu => $data) {
            $guvenSkoru = 0;
            $count = count($data['ids']);
            foreach ($data['ids'] as $id) {
                $guvenSkoru += $roomResults[$id]['guven_skoru'] ?? 0;
            }
            $avgGuven = $count > 0 ? $guvenSkoru / $count : 0;

            $dtos[] = new MediaRoomDTO(
                oda_turu: $turu,
                label: $data['label'],
                guven_skoru: (int) $avgGuven,
                fotograf_sayisi: $count,
                fotograf_ids: $data['ids'],
            );
        }

        return $dtos;
    }

    /**
     * Sonuçları DB'ye kaydet.
     */
    private function persist(
        Ilan $ilan,
        array $photoDTOs,
        array $heroData,
        array $coverageResult,
        int $healthScore,
        int $qualityScore,
    ): void {
        $oldHealth = $ilan->media_health_score ?? 0;

        // Hero photo
        $heroFotografId = $heroData['hero_fotograf_id'];

        // Update hero photograph
        if ($heroFotografId !== null) {
            IlanFotografi::where('ilan_id', $ilan->id)
                ->where('id', $heroFotografId)
                ->update(['kapak_fotografi' => true]);
            // Clear other cover photos
            IlanFotografi::where('ilan_id', $ilan->id)
                ->where('id', '<>', $heroFotografId)
                ->update(['kapak_fotografi' => false]);
        }

        // Update photo-level data
        foreach ($photoDTOs as $dto) {
            IlanFotografi::where('id', $dto->fotograf_id)->update([
                'oda_turu' => $dto->oda_turu,
                'oda_turu_guven' => $dto->oda_guven_skoru,
                'kalite_puani' => $dto->kalite_puani,
                'kalite_ayrinti' => json_encode($dto->kalite_ayrinti),
                'hero_skoru' => $heroData['hero_score'] ?? 0,
                'media_data' => json_encode([
                    'oda_turu' => $dto->oda_turu,
                    'kalite_puani' => $dto->kalite_puani,
                    'hero_skoru' => $heroData['hero_score'] ?? 0,
                ]),
            ]);
        }

        // Update ilan-level data
        $ilan->media_health_score = $healthScore;
        $ilan->media_quality_score = $qualityScore;
        $ilan->media_tamamlanma_oran = (int) round((count($photoDTOs) / 10) * 100);
        $ilan->eksik_odalar = $coverageResult['missing_rooms'];
        $ilan->hero_fotograf_id = $heroFotografId;
        $ilan->save();
    }

    /**
     * Event'leri fırlat.
     */
    private function dispatchEvents(
        int $ilanId,
        int $healthScore,
        int $qualityScore,
        int $totalPhotos,
        ?int $heroId,
        array $missingRooms,
    ): void {
        event(new MediaAnalyzed(
            ilan_id: $ilanId,
            media_health_score: $healthScore,
            toplam_fotograf: $totalPhotos,
            hero_fotograf_id: $heroId,
            eksik_odalar: $missingRooms,
            media_quality_score: $qualityScore,
        ));

        if ($heroId !== null) {
            event(new HeroImageSelected(
                ilan_id: $ilanId,
                hero_fotograf_id: $heroId,
                hero_skoru: 0.0,
            ));
        }
    }

    /**
     * Boş sonuç döner (fotoğraf yoksa).
     */
    private function emptyResult(int $ilanId): MediaAnalysisDTO
    {
        return new MediaAnalysisDTO(
            ilan_id: $ilanId,
            toplam_fotograf: 0,
            media_health_score: 0,
            media_quality_score: 0,
            tamamlanma_oran: 0.0,
            oda_detaylari: [],
            eksik_odalar: $this->coverageAnalyzer->getRequiredRooms(),
            hero_fotograf_id: null,
            tum_fotograflar: [],
        );
    }
}
