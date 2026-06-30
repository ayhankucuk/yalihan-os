<?php

namespace App\Services\AIDeal;

use App\Models\Ilan;
use App\Models\DealPredictionLog;
use Illuminate\Support\Facades\Log;

/**
 * ️ SAB SEALED
 * Deal Prediction Service
 * The main entry point for the AI Deal Predictor Engine.
 * Orchestrates scoring, explanation, and telemetry.
 */
class DealPredictionService
{
    protected DealScoringService $scoringService;
    protected DealExplanationService $explanationService;
    protected DealTelemetryService $telemetryService;

    public function __construct(
        DealScoringService $scoringService,
        DealExplanationService $explanationService,
        DealTelemetryService $telemetryService
    ) {
        $this->scoringService = $scoringService;
        $this->explanationService = $explanationService;
        $this->telemetryService = $telemetryService;
    }

    /**
     * Perform a complete deal analysis for a listing.
     */
    public function predict(Ilan $ilan, array $options = []): array
    {
        $startTime = microtime(true);

        try {
            // 1. Calculate Scores
            $scores = $this->scoringService->calculateAll($ilan);

            // 2. Generate Explanation
            $locale = $options['locale'] ?? app()->getLocale();
            $explanation = $this->explanationService->explain($scores, $locale);

            // 3. Log Telemetry
            $log = $this->telemetryService->logPrediction($ilan, $scores, $explanation, [
                'duration' => microtime(true) - $startTime,
                'trigger' => $options['trigger'] ?? 'manual',
            ]);

            // 4. (Optional) Snapshot if specifically requested or daily
            if ($options['snapshot'] ?? false) {
                $this->telemetryService->createSnapshot($ilan, $scores);
            }

            return [
                'prediction_id' => $log->id,
                'scores' => $scores,
                'explanation' => $explanation,
                'meta' => [
                    'model' => $log->model_version,
                    'timestamp' => $log->created_at,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Deal Prediction failed', [
                'listing_id' => $ilan->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
