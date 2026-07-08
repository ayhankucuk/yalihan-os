<?php

namespace App\Services\Media;

use App\DTOs\Media\MediaRoomDTO;

/**
 * Coverage Analyzer — Sprint 6.3
 *
 * Tespit edilen odalar vs. gerekli odaları karşılaştırır.
 * Eksik odaları belirler.
 */
class CoverageAnalyzer
{
    /** @var string[] Gerekli oda türleri (sıra önemli — öncelik) */
    private const REQUIRED_ROOMS = [
        'living_room',
        'bedroom',
        'bathroom',
        'kitchen',
        'pool',
        'garden',
        'terrace',
        'exterior',
        'view',
    ];

    /**
     * Coverage oranını ve eksik odaları hesapla.
     *
     * @param  MediaRoomDTO[]  $detectedRooms
     * @return array{coverage: float, missing_rooms: string[], detected_room_types: string[], all_required: bool}
     */
    public function analyze(array $detectedRooms): array
    {
        $detectedTypes = array_column(array_map(fn($r) => $r->toArray(), $detectedRooms), 'oda_turu');
        $detectedTypesSet = array_flip($detectedTypes);

        $missingRooms = [];
        foreach (self::REQUIRED_ROOMS as $required) {
            if (!isset($detectedTypesSet[$required])) {
                $missingRooms[] = $required;
            }
        }

        $coverage = count($detectedTypes) > 0
            ? count($detectedTypes) / count(self::REQUIRED_ROOMS)
            : 0.0;

        return [
            'coverage' => min(1.0, $coverage),
            'missing_rooms' => $missingRooms,
            'detected_room_types' => array_values($detectedTypesSet),
            'all_required' => empty($missingRooms),
        ];
    }

    /**
     * Coverage skoru (0–100).
     */
    public function getCoverageScore(array $detectedRooms): int
    {
        $result = $this->analyze($detectedRooms);
        return (int) min(100, round($result['coverage'] * 100));
    }

    /**
     * Gerekli oda listesini döndür.
     *
     * @return string[]
     */
    public function getRequiredRooms(): array
    {
        return self::REQUIRED_ROOMS;
    }
}
