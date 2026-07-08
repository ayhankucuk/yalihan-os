<?php

namespace App\Services\Media;

use App\Models\IlanFotografi;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

/**
 * Image Quality Engine — Sprint 6.3
 *
 * Kural tabanlı görüntü kalitesi analizi (V1).
 *
 * Hesaplanan metrikler:
 *   blur_score    — Laplacian variance (keskinlik ölçümü)
 *   brightness   — Histogram mean (parlaklık 0–255 → 0–100)
 *   exposure     — Histogram Dağılımı (100 = perfectly balanced)
 *   sharpness    — Ken yoğunluğu (edge density)
 *   resolution   — Piksel sayısı / optimal eşik
 *   quality_score — Ağırlıklı ortalama (0–100)
 *
 * AI Vision (GPT-4 Vision / DeepSeek Vision) sonraki sprint.
 */
class ImageQualityEngine
{
    private const OPTIMAL_MIN_WIDTH = 1200;
    private const OPTIMAL_MIN_HEIGHT = 800;
    private const QUALITY_WEIGHTS = [
        'blur' => 0.30,
        'brightness' => 0.20,
        'exposure' => 0.20,
        'sharpness' => 0.15,
        'resolution' => 0.15,
    ];

    /**
     * Tek bir fotoğrafın kalite analizini yap.
     *
     * @param  IlanFotografi  $fotograf
     * @return array{
     *     blur_score: int,
     *     brightness: int,
     *     exposure: int,
     *     sharpness: int,
     *     resolution: int,
     *     quality_score: int,
     *     width: int|null,
     *     height: int|null
     * }
     */
    public function analyze(IlanFotografi $fotograf): array
    {
        $path = $this->getAbsolutePath($fotograf);

        if ($path === null || !file_exists($path)) {
            return $this->defaultResult();
        }

        $imageInfo = @getimagesize($path);
        if ($imageInfo === false) {
            return $this->defaultResult();
        }

        [$width, $height, $type] = $imageInfo;

        // Resolution score
        $resolutionScore = $this->calcResolutionScore($width, $height);

        // Open image for pixel analysis
        $resource = $this->openImage($path, $type);
        if ($resource === null) {
            return $this->defaultResultWithResolution($width, $height, $resolutionScore);
        }

        try {
            // Blur detection (Laplacian variance approximation)
            $blurScore = $this->calcBlurScore($resource, $width, $height);

            // Brightness (histogram mean)
            $brightnessScore = $this->calcBrightnessScore($resource, $width, $height);

            // Exposure (histogram spread)
            $exposureScore = $this->calcExposureScore($resource, $width, $height);

            // Sharpness (Sobel edge detection approximation)
            $sharpnessScore = $this->calcSharpnessScore($resource, $width, $height);

            // Weighted quality score
            $qualityScore = $this->calcWeightedScore($blurScore, $brightnessScore, $exposureScore, $sharpnessScore, $resolutionScore);

            return [
                'blur_score' => $blurScore,
                'brightness' => $brightnessScore,
                'exposure' => $exposureScore,
                'sharpness' => $sharpnessScore,
                'resolution' => $resolutionScore,
                'quality_score' => $qualityScore,
                'width' => $width,
                'height' => $height,
            ];
        } finally {
            if (is_resource($resource)) {
                imagedestroy($resource);
            }
        }
    }

    /**
     * Birden fazla fotoğrafı analiz et.
     *
     * @param  IlanFotografi[]  $fotograflar
     * @return array<int, array{...}> key = fotograf_id
     */
    public function analyzeBatch(array $fotograflar): array
    {
        $results = [];
        foreach ($fotograflar as $fotograf) {
            $results[$fotograf->id] = $this->analyze($fotograf);
        }
        return $results;
    }

    /**
     * Mutlak dosya yolunu al.
     */
    private function getAbsolutePath(IlanFotografi $fotograf): ?string
    {
        if (empty($fotograf->dosya_yolu)) {
            return null;
        }

        // Disk üzerinden mutlak yol
        if (Str::startsWith($fotograf->dosya_yolu, '/')) {
            return public_path($fotograf->dosya_yolu);
        }

        return public_path('storage/' . $fotograf->dosya_yolu);
    }

    /**
     * Görüntüyü aç.
     *
     * @return resource|GdImage|null
     */
    private function openImage(string $path, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };
    }

    /**
     * Resolution score (0–100).
     */
    private function calcResolutionScore(int $width, int $height): int
    {
        $pixels = $width * $height;
        $optimal = self::OPTIMAL_MIN_WIDTH * self::OPTIMAL_MIN_HEIGHT;

        $ratio = $pixels / $optimal;

        if ($ratio >= 1.0) {
            return 100;
        }

        // Sub-linear: 0.5x → ~70, 0.25x → ~40
        return (int) min(100, round(sqrt($ratio) * 100));
    }

    /**
     * Blur score — Laplacian variance approximation.
     * Keskin olmayan görüntüler düşük skor alır.
     */
    private function calcBlurScore($resource, int $width, int $height): int
    {
        // Downsample for performance
        $w = min(100, $width);
        $h = min(100, $height);
        $sample = imagecreatetruecolor($w, $h);
        imagecopyresampled($sample, $resource, 0, 0, 0, 0, $w, $h, $width, $height);

        // Convert to grayscale and compute variance
        $gray = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($sample, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray[] = (int) (0.299 * $r + 0.587 * $g + 0.114 * $b);
            }
        }
        imagedestroy($sample);

        // Laplacian: compute second derivative variance
        $variance = $this->laplacianVariance($gray, $w, $h);

        // Normalize: variance 100+ = sharp (100), <10 = blurry (0)
        if ($variance >= 1000) {
            return 100;
        }
        if ($variance <= 10) {
            return 5;
        }

        return (int) min(100, round(($variance / 1000) * 100));
    }

    /**
     * Laplacian variance approximation.
     */
    private function laplacianVariance(array $gray, int $w, int $h): float
    {
        $lapValues = [];
        for ($y = 1; $y < $h - 1; $y++) {
            for ($x = 1; $x < $w - 1; $x++) {
                $center = $gray[$y * $w + $x];
                $neighbors = [
                    $gray[($y - 1) * $w + $x],
                    $gray[($y + 1) * $w + $x],
                    $gray[$y * $w + ($x - 1)],
                    $gray[$y * $w + ($x + 1)],
                ];
                $lap = 4 * $center - array_sum($neighbors);
                $lapValues[] = $lap * $lap;
            }
        }

        if (empty($lapValues)) {
            return 0.0;
        }

        $mean = array_sum($lapValues) / count($lapValues);
        $variance = 0.0;
        foreach ($lapValues as $v) {
            $variance += ($v - $mean) * ($v - $mean);
        }

        return $variance / count($lapValues);
    }

    /**
     * Brightness score — histogram mean (0–255 → 0–100).
     * Optimal: 100–180 aralığında.
     */
    private function calcBrightnessScore($resource, int $width, int $height): int
    {
        // Sample every 10th pixel
        $step = 10;
        $total = 0;
        $count = 0;

        for ($y = 0; $y < $height; $y += $step) {
            for ($x = 0; $x < $width; $x += $step) {
                $rgb = imagecolorat($resource, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $total += (int) (0.299 * $r + 0.587 * $g + 0.114 * $b);
                $count++;
            }
        }

        if ($count === 0) {
            return 50;
        }

        $mean = $total / $count;

        // Optimal: 100–180 aralığında = 100 puan
        // Under: <100, dark: <50, over: >180, bright: >220
        if ($mean >= 100 && $mean <= 180) {
            return 100;
        }
        if ($mean < 100) {
            return max(10, (int) round(($mean / 100) * 100));
        }
        // Overexposed
        $over = $mean - 180;
        return max(10, 100 - (int) round($over * 0.5));
    }

    /**
     * Exposure score — histogram spread.
     * Çok karanlık veya çok parlak görüntüler düşük skor alır.
     */
    private function calcExposureScore($resource, int $width, int $height): int
    {
        $histR = array_fill(0, 256, 0);
        $histG = array_fill(0, 256, 0);
        $histB = array_fill(0, 256, 0);

        $step = 10;
        $count = 0;

        for ($y = 0; $y < $height; $y += $step) {
            for ($x = 0; $x < $width; $x += $step) {
                $rgb = imagecolorat($resource, $x, $y);
                $histR[($rgb >> 16) & 0xFF]++;
                $histG[($rgb >> 8) & 0xFF]++;
                $histB[$rgb & 0xFF]++;
                $count++;
            }
        }

        if ($count === 0) {
            return 50;
        }

        // Clipped pixels (under/over exposed)
        $clippedThreshold = (int) ($count * 0.01); // >1% clipped
        $underExposed = 0;
        $overExposed = 0;

        for ($i = 0; $i < 20; $i++) {
            $underExposed += $histR[$i] + $histG[$i] + $histB[$i];
        }
        for ($i = 236; $i < 256; $i++) {
            $overExposed += $histR[$i] + $histG[$i] + $histB[$i];
        }

        $clippedTotal = $underExposed + $overExposed;
        $clipRatio = $clippedTotal / ($count * 3);

        // <1% clipped = 100, >25% clipped = 0
        if ($clipRatio <= 0.01) {
            return 100;
        }

        return max(5, (int) round(100 - ($clipRatio * 400)));
    }

    /**
     * Sharpness score — simplified edge density.
     */
    private function calcSharpnessScore($resource, int $width, int $height): int
    {
        // Sobel operator approximation
        $w = min(80, $width);
        $h = min(80, $height);
        $sample = imagecreatetruecolor($w, $h);
        imagecopyresampled($sample, $resource, 0, 0, 0, 0, $w, $h, $width, $height);

        $edgeSum = 0;
        $edgeCount = 0;

        for ($y = 1; $y < $h - 1; $y++) {
            for ($x = 1; $x < $w - 1; $x++) {
                $c = imagecolorat($sample, $x, $y) & 0xFF;
                $n = imagecolorat($sample, $x, $y - 1) & 0xFF;
                $s = imagecolorat($sample, $x, $y + 1) & 0xFF;
                $e = imagecolorat($sample, $x + 1, $y) & 0xFF;
                $w2 = imagecolorat($sample, $x - 1, $y) & 0xFF;

                $gx = abs($e - $w2);
                $gy = abs($s - $n);
                $edgeSum += sqrt($gx * $gx + $gy * $gy);
                $edgeCount++;
            }
        }

        imagedestroy($sample);

        if ($edgeCount === 0) {
            return 0;
        }

        $avgEdge = $edgeSum / $edgeCount;

        // Normalize: 20+ = sharp (100), 5 = blurry (0)
        return (int) min(100, max(0, round($avgEdge * 5)));
    }

    /**
     * Ağırlıklı kalite skoru hesapla.
     */
    private function calcWeightedScore(
        int $blurScore,
        int $brightnessScore,
        int $exposureScore,
        int $sharpnessScore,
        int $resolutionScore,
    ): int {
        $score = (
            $blurScore * self::QUALITY_WEIGHTS['blur'] +
            $brightnessScore * self::QUALITY_WEIGHTS['brightness'] +
            $exposureScore * self::QUALITY_WEIGHTS['exposure'] +
            $sharpnessScore * self::QUALITY_WEIGHTS['sharpness'] +
            $resolutionScore * self::QUALITY_WEIGHTS['resolution']
        );

        return (int) min(100, max(0, round($score)));
    }

    private function defaultResult(): array
    {
        return [
            'blur_score' => 0,
            'brightness' => 0,
            'exposure' => 0,
            'sharpness' => 0,
            'resolution' => 0,
            'quality_score' => 0,
            'width' => null,
            'height' => null,
        ];
    }

    private function defaultResultWithResolution(int $width, int $height, int $resolutionScore): array
    {
        return [
            'blur_score' => 0,
            'brightness' => 0,
            'exposure' => 0,
            'sharpness' => 0,
            'resolution' => $resolutionScore,
            'quality_score' => $resolutionScore,
            'width' => $width,
            'height' => $height,
        ];
    }
}
