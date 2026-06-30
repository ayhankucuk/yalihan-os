<?php

namespace App\Services\AI\Copilot;

use App\Models\Ilan;
use App\Services\AI\IntelligenceHub;
use App\Services\AI\YalihanCortex;
use App\Services\AIDeal\DealPredictionService;
use Illuminate\Support\Facades\Log;

/**
 * 🛡️ SAB SEALED
 * Broker Copilot Service
 * Orchestrates multiple AI engines to provide contextual advice to agents.
 */
class BrokerCopilotService
{
    protected IntelligenceHub $intelligenceHub;
    protected \App\Application\AI\Actions\RecommendNextActionsAction $recommendAction;
    protected DealPredictionService $dealPredictor;

    public function __construct(
        IntelligenceHub $intelligenceHub,
        \App\Application\AI\Actions\RecommendNextActionsAction $recommendAction,
        DealPredictionService $dealPredictor
    ) {
        $this->intelligenceHub = $intelligenceHub;
        $this->recommendAction = $recommendAction;
        $this->dealPredictor = $dealPredictor;
    }

    /**
     * Analyze a listing and answer a specific question using orchestrator pattern.
     */
    public function analyze(Ilan $ilan, string $question = ''): array
    {
        try {
            // 1. Get Listing Health (Market + Quality + SEO)
            $health = $this->intelligenceHub->getListingHealth($ilan->id);

            // 2. Get Deal Prediction (Probability + Timing)
            $predictionData = $this->dealPredictor->predict($ilan, ['trigger' => 'copilot']);

            // 3. Generate Analysis & Recommendations via Cortex (AI)
            $cortexResponse = $this->recommendAction->execute([
                'ilan' => $ilan->only(['id', 'baslik', 'fiyat', 'para_birimi', 'kategori_id', 'yayin_durumu']),
                'health' => $health,
                'prediction' => $predictionData
            ], ['question' => $question]);

            $recommendations = $cortexResponse->success
                ? $cortexResponse->output
                : ['error' => 'AI Recommendations could not be generated.'];

            // 4. Construct Final Response
            return [
                'analysis' => [
                    'title' => $ilan->baslik,
                    'overall_health' => $health['overall_health'] ?? 0,
                    'health_label' => $this->intelligenceHub->getScoreLabel($health['overall_health'] ?? 0),
                    'breakdown' => $health['scores'] ?? [],
                    'details' => $health,
                ],
                'prediction' => [
                    'probability' => $predictionData['scores']['total'] ?? 0,
                    'explanation' => $predictionData['explanation'] ?? '',
                    'meta' => $predictionData['meta'] ?? [],
                ],
                'recommendation' => $recommendations,
                'confidence' => $this->calculateConfidence($health, $predictionData),
                'question_context' => [
                    'question' => $question,
                    'timestamp' => now()->toIso8601String(),
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Broker Copilot Analysis failed: ' . $e->getMessage(), [
                'ilan_id' => $ilan->id,
                'question' => $question
            ]);
            throw $e;
        }
    }

    /**
     * Helper to calculate aggregate confidence score based on engine outputs.
     */
    protected function calculateConfidence(array $health, array $prediction): float
    {
        // Simple weighted confidence based on availability of data points
        $points = 0;
        if (!empty($health['market_data'])) $points += 40;
        if (!empty($health['quality_data'])) $points += 30;
        if (!empty($prediction['scores'])) $points += 30;

        return min(100, $points);
    }
}
