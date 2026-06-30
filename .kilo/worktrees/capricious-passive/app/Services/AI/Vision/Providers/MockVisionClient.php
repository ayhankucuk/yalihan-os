<?php

namespace App\Services\AI\Vision\Providers;

use App\Services\AI\Vision\Contracts\VisionAnalysisContract;
use App\Services\AI\SmartFieldGenerationService;

/**
 * Alternative for simulation during local development
 * Phase 8: Mock provider for testing
 */
class MockVisionClient implements VisionAnalysisContract
{
    protected SmartFieldGenerationService $aiService;

    public function __construct(SmartFieldGenerationService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function analyze(array $imageUrls, array $context = []): array
    {
        $extractedItems = [];

        foreach ($imageUrls as $img) {
            // Re-use existing text-based simulation for mock
            $textFeatures = $this->aiService->extractFromText($img);

            // Explicit Mock Logic for Tests (Havuz)
            if (str_contains(strtolower($img), 'havuz')) {
                 $textFeatures[] = [
                    'slug' => 'ortak-havuz',
                    'confidence' => 0.95,
                    'count' => 1
                 ];
            }

            foreach ($textFeatures as $item) {
                $item['source'] = 'image';
                $item['source_reference'] = basename($img);
                // Ensure confidence is 0.85 for test consistency
                $item['confidence'] = 0.85;
                $item['reason'] = "Dosya adında '" . $img . "' geçtiği için önerildi (Mock)";

                // Explainability for tests
                $item['explainability_detail'] = [
                    'source' => 'image',
                    'signals' => ['simulation' => true],
                    'confidence_factors' => ['filename_match']
                ];

                $extractedItems[] = $item;
            }
        }

        // Apply governance thresholds & deduplication via standard service
        $suggestions = $this->aiService->generateSmartRecommendations(
            $extractedItems,
            $context['category_id'] ?? null,
            null // yayin_tipi check is handled by orchestrator UPS Guard
        );

        return [
            'suggestions' => $suggestions,
            'signals' => ['simulation' => true],
            'cost_estimate' => 0.0
        ];
    }
}
