<?php

declare(strict_types=1);

namespace App\Services\Location;

use App\Models\Ilan;
use App\Services\Ilan\IlanCrudService;
use Illuminate\Support\Facades\Log;

/**
 * NeighborhoodScoringService
 *
 * Sprint 6.2: Calculates Walkability & Noise Ratings based on surrounding POIs.
 */
class NeighborhoodScoringService
{
    public function __construct(
        private readonly IlanCrudService $ilanCrudService
    ) {}

    /**
     * Calculate and persist scores for a listing.
     */
    public function calculateAndPersist(Ilan $ilan): array
    {
        $currentMeta = $ilan->metadata ?? [];
        $locIntel = $currentMeta['location_intelligence'] ?? [];
        $pois = $locIntel['pois'] ?? [];

        $walkScore = $this->calculateWalkScore($pois);
        $noiseScore = $this->calculateNoiseScore($pois);

        $locIntel['walk_score'] = $walkScore;
        $locIntel['noise_score'] = $noiseScore;

        $this->ilanCrudService->update($ilan, [
            'metadata' => [
                'location_intelligence' => $locIntel,
            ],
        ]);

        Log::info('NeighborhoodScoringService: Scores calculated and persisted', [
            'ilan_id'     => $ilan->id,
            'walk_score'  => $walkScore,
            'noise_score' => $noiseScore,
        ]);

        return [
            'walk_score'  => $walkScore,
            'noise_score' => $noiseScore,
        ];
    }

    /**
     * Calculate Walk Score (0-100)
     */
    public function calculateWalkScore(array $pois): int
    {
        if (empty($pois)) {
            return 50; // Neutral default baseline
        }

        $score = 0;

        foreach ($pois as $poi) {
            $type = $poi['type'] ?? 'other'; // context7-ignore
            $dist = (int) ($poi['distance_meters'] ?? 5000);

            // Apply distance decay weights
            $points = 0;
            switch ($type) { // context7-ignore
                case 'beach':
                    if ($dist <= 500) {
                        $points = 35;
                    } elseif ($dist <= 1500) {
                        $points = (int) (35 * (1 - ($dist - 500) / 1000));
                    }
                    break;

                case 'marina':
                    if ($dist <= 800) {
                        $points = 25;
                    } elseif ($dist <= 2000) {
                        $points = (int) (25 * (1 - ($dist - 800) / 1200));
                    }
                    break;

                case 'school':
                    if ($dist <= 1000) {
                        $points = 20;
                    } elseif ($dist <= 2000) {
                        $points = (int) (20 * (1 - ($dist - 1000) / 1000));
                    }
                    break;

                case 'hospital':
                    if ($dist <= 1500) {
                        $points = 20;
                    } elseif ($dist <= 3000) {
                        $points = (int) (20 * (1 - ($dist - 1500) / 1500));
                    }
                    break;
            }

            $score += $points;
        }

        return (int) min(100, max(10, $score));
    }

    /**
     * Calculate Noise Score (0-100)
     */
    public function calculateNoiseScore(array $pois): int
    {
        if (empty($pois)) {
            return 25; // Quiet residential default
        }

        $noiseScore = 20; // Quiet baseline

        foreach ($pois as $poi) {
            $name = strtolower($poi['name'] ?? '');
            $dist = (int) ($poi['distance_meters'] ?? 5000);

            // Check proximity to high-noise sources (e.g. highways, main roads, clubs)
            if (($poi['type'] ?? '') === 'marina' && $dist <= 300) { // context7-ignore
                $noiseScore = max($noiseScore, 45); // Medium noise near busy harbor
            }

            if ((str_contains($name, 'otoyol') || str_contains($name, 'karayolu') || str_contains($name, 'highway')) && $dist <= 150) {
                $noiseScore = max($noiseScore, 85); // High noise near arterial highway
            }

            if ((str_contains($name, 'club') || str_contains($name, 'bar') || str_contains($name, 'disko')) && $dist <= 250) {
                $noiseScore = max($noiseScore, 70); // High noise near nightlife
            }
        }

        return $noiseScore;
    }
}
