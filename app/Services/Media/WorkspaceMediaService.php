<?php

namespace App\Services\Media;

use App\DTOs\Media\MediaSummaryDTO;
use App\Models\Ilan;

/**
 * Workspace Media Service — Sprint 6.3
 *
 * Workspace payload için media intelligence verisi sağlar.
 * Mevcut Workspace API'lerini bozmaz — sadece yeni media payload ekler.
 */
class WorkspaceMediaService
{
    /**
     * Bir ilan için media summary DTO döndür.
     */
    public function getSummary(Ilan $ilan): MediaSummaryDTO
    {
        if ($ilan->media_health_score === null) {
            return MediaSummaryDTO::empty();
        }

        $detectedRooms = $this->extractDetectedRooms($ilan);
        $missingRooms = $ilan->eksik_odalar ?? [];
        $heroUrl = $this->getHeroImageUrl($ilan);

        return new MediaSummaryDTO(
            health: $this->getHealthLabel($ilan->media_health_score),
            health_score: (int) $ilan->media_health_score,
            quality_score: (int) ($ilan->media_quality_score ?? 0),
            coverage: $this->calculateCoverage($ilan),
            hero_image_url: $heroUrl,
            detected_rooms: $detectedRooms,
            missing_rooms: $missingRooms,
            total_photos: $ilan->fotograflar()->count(),
        );
    }

    /**
     * Workspace payload formatında media verisini döndür.
     */
    public function getWorkspacePayload(Ilan $ilan): array
    {
        return $this->getSummary($ilan)->toArray();
    }

    private function extractDetectedRooms(Ilan $ilan): array
    {
        $rooms = [];

        $fotograflar = $ilan->fotograflar()
            ->whereNotNull('oda_turu')
            ->selectRaw('oda_turu, COUNT(*) as cnt, MAX(oda_turu_guven) as max_guven')
            ->groupBy('oda_turu')
            ->get();

        foreach ($fotograflar as $row) {
            $rooms[] = [
                'oda_turu' => $row->oda_turu,
                'label' => $this->getRoomLabel($row->oda_turu),
                'count' => (int) $row->cnt,
                'guven_skoru' => (int) $row->max_guven,
            ];
        }

        return $rooms;
    }

    private function getHeroImageUrl(Ilan $ilan): ?string
    {
        if (!$ilan->hero_fotograf_id) {
            return null;
        }

        $hero = $ilan->fotograflar()
            ->where('id', $ilan->hero_fotograf_id)
            ->first();

        return $hero?->url;
    }

    private function calculateCoverage(Ilan $ilan): float
    {
        $detected = $ilan->fotograflar()
            ->whereNotNull('oda_turu')
            ->distinct('oda_turu')
            ->count('oda_turu');

        $required = 9; // CoverageAnalyzer::REQUIRED_ROOMS count
        return min(1.0, $detected / $required);
    }

    private function getHealthLabel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'EXCELLENT',
            $score >= 60 => 'GOOD',
            $score >= 40 => 'FAIR',
            $score >= 20 => 'POOR',
            default => 'MISSING',
        };
    }

    private function getRoomLabel(string $odaTuru): string
    {
        return match ($odaTuru) {
            'pool' => 'Havuz',
            'view' => 'Manzara',
            'living_room' => 'Salon',
            'bedroom' => 'Yatak Odası',
            'kitchen' => 'Mutfak',
            'bathroom' => 'Banyo',
            'terrace' => 'Teras',
            'garden' => 'Bahçe',
            'exterior' => 'Dış Cephe',
            default => 'Diğer',
        };
    }
}
