<?php

namespace App\Services\Photo;

use Illuminate\Support\Facades\Log;

/**
 * Photo Quality Analysis Service
 * MVP: Simple quality scoring based on technical metrics
 */
class PhotoAnalysisService
{
    /**
     * Analyze photo quality
     * Returns: { quality_score: 0-100, metrics: {...}, suggestions: [...] }
     */
    public function analyzePhoto(string $filePath): array
    {
        try {
            // Basic validation
            if (!file_exists($filePath)) {
                return $this->defaultAnalysis();
            }

            // Get image dimensions
            $imageInfo = getimagesize($filePath);
            if ($imageInfo === false) {
                return $this->defaultAnalysis();
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];

            // Simple scoring formula (MVP)
            // 1. Resolution score (0-40)
            $resolutionScore = $this->scoreResolution($width, $height);

            // 2. Aspect ratio score (0-30) - prefer portrait for advisor photos
            $aspectScore = $this->scoreAspectRatio($width, $height);

            // 3. File size optimization (0-20)
            $fileSizeScore = $this->scoreFileSize($filePath);

            // 4. Generic professional boost (0-10)
            $professionalScore = 10; // MVP: all uploaded photos are assumed professional

            $totalScore = $resolutionScore + $aspectScore + $fileSizeScore + $professionalScore;

            return [
                'quality_score' => round($totalScore, 2),
                'quality_metrics' => [
                    'composition' => $resolutionScore,
                    'lighting' => $aspectScore,
                    'focus' => $fileSizeScore,
                    'faces' => $professionalScore,
                ],
                'analysis_details' => [
                    'width' => $width,
                    'height' => $height,
                    'aspect_ratio' => round($width / $height, 2),
                    'file_size_mb' => round(filesize($filePath) / (1024 * 1024), 2),
                    'mime_type' => mime_content_type($filePath),
                ],
                'improvement_suggestions' => $this->generateSuggestions($width, $height, $filePath),
                'visual_keywords' => ['professional', 'portrait'],
            ];
        } catch (\Exception $e) {
            Log::error('Photo analysis failed', ['error' => $e->getMessage()]);
            return $this->defaultAnalysis();
        }
    }

    /**
     * Score resolution (0-40)
     */
    private function scoreResolution(int $width, int $height): float
    {
        $megapixels = ($width * $height) / 1000000;

        if ($megapixels >= 5) {
            return 40; // 5MP+ = excellent
        } elseif ($megapixels >= 2) {
            return 30; // 2-5MP = good
        } elseif ($megapixels >= 1) {
            return 20; // 1-2MP = acceptable
        } else {
            return 10; // < 1MP = poor
        }
    }

    /**
     * Score aspect ratio (0-30)
     * Best for advisor profiles: portrait (0.6-0.8 ratio)
     */
    private function scoreAspectRatio(int $width, int $height): float
    {
        $aspectRatio = $width / $height;

        // Portrait format (0.6-0.8) = ideal for advisor photos
        if ($aspectRatio >= 0.6 && $aspectRatio <= 0.8) {
            return 30; // Perfect
        }
        // Near portrait (0.5-0.9)
        elseif ($aspectRatio >= 0.5 && $aspectRatio <= 0.9) {
            return 25; // Good
        }
        // Square (0.9-1.1)
        elseif ($aspectRatio >= 0.9 && $aspectRatio <= 1.1) {
            return 20; // Acceptable
        }
        // Landscape
        else {
            return 10; // Poor for advisor portraits
        }
    }

    /**
     * Score file size (0-20)
     * Optimal: 500KB-2MB
     */
    private function scoreFileSize(string $filePath): float
    {
        $fileSizeKb = filesize($filePath) / 1024;

        if ($fileSizeKb >= 500 && $fileSizeKb <= 2000) {
            return 20; // Perfect
        } elseif ($fileSizeKb >= 300 && $fileSizeKb <= 3000) {
            return 15; // Good
        } elseif ($fileSizeKb >= 100 && $fileSizeKb <= 5000) {
            return 10; // Acceptable
        } else {
            return 5; // Poor (too small or too large)
        }
    }

    /**
     * Generate improvement suggestions
     */
    private function generateSuggestions(int $width, int $height, string $filePath): array
    {
        $suggestions = [];

        // Resolution check
        $megapixels = ($width * $height) / 1000000;
        if ($megapixels < 2) {
            $suggestions[] = "Daha yüksek çözünürlükte bir resim yükleyin (min. 2MP)";
        }

        // Aspect ratio check
        $aspectRatio = $width / $height;
        if ($aspectRatio > 1.2) {
            $suggestions[] = "Yatay formatta resim yerine dik formatta resim deneyin";
        }

        // File size check
        $fileSizeKb = filesize($filePath) / 1024;
        if ($fileSizeKb > 5000) {
            $suggestions[] = "Dosya boyutunu küçültün (sıkıştırın)";
        }

        if (empty($suggestions)) {
            $suggestions[] = "Mükemmel resim kalitesi!";
        }

        return $suggestions;
    }

    /**
     * Default analysis when image cannot be processed
     */
    private function defaultAnalysis(): array
    {
        return [
            'quality_score' => 50,
            'quality_metrics' => [
                'composition' => 15,
                'lighting' => 15,
                'focus' => 10,
                'faces' => 10,
            ],
            'analysis_details' => [],
            'improvement_suggestions' => [
                'Resim analiz edilemedi. Farklı bir resim deneyin.',
            ],
            'visual_keywords' => [],
        ];
    }
}
