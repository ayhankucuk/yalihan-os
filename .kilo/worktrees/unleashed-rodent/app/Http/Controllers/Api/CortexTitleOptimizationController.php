<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\YalihanCortex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Cortex Smart Title API Controller
 * Context7: AI-powered SEO optimization for property listings
 */
class CortexTitleOptimizationController extends Controller
{
    public function __construct(
        protected YalihanCortex $cortex
    ) {}

    /**
     * İlan başlığını AI ile optimize et
     * POST /api/v1/ai/optimize-title
     */
    public function optimize(Request $request)
    {
        $request->validate([
            'baslik' => 'nullable|string|max:200',
            'ana_kategori_id' => 'nullable',
            'il_id' => 'nullable',
            'ilce_id' => 'nullable',
            'mahalle_id' => 'nullable',
            'ozellik_ids' => 'nullable|array',
        ]);

        try {
            $result = $this->cortex->optimizeIlanTitle($request->all());

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Optimizasyon başarısız oldu.',
                    'fallback_title' => $result['fallback_title'] ?? $request->baslik
                ], 422);
            }

            // Ek metrikler (V3 Intelligence)
            $result['improvements'] = [
                'seo_score' => $this->calculateSEOScore($result['optimized_title']),
                'click_potential' => $this->estimateClickRate($result['optimized_title']),
                'keywords_found' => $this->extractKeywords($result['optimized_title'])
            ];

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Cortex Title API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sistem hatası oluştu.'
            ], 500);
        }
    }

    protected function calculateSEOScore(string $title): int
    {
        $score = 50;
        $length = mb_strlen($title);
        if ($length >= 40 && $length <= 70) $score += 30;
        if (preg_match('/[0-9]/', $title)) $score += 10; // Fiyat veya oda sayısı vurgusu
        return min(100, $score);
    }

    protected function estimateClickRate(string $title): float
    {
        $baseCrr = 1.2;
        $powerWords = ['Fırsat', 'Lüks', 'Deniz', 'Manzara', 'Acil', 'Kupon', 'Uygun'];
        foreach ($powerWords as $word) {
            if (mb_stripos($title, $word) !== false) $baseCrr += 0.4;
        }
        return round($baseCrr, 1);
    }

    protected function extractKeywords(string $title): array
    {
        return array_filter(explode(' ', $title), fn($w) => mb_strlen($w) > 3);
    }
}
