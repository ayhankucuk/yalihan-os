<?php

namespace App\Application\AI\Support;

use App\Application\AI\DTOs\CortexRequestData;
use App\Domain\AI\Enums\AITaskType;

/**
 * 🛡️ RequestComplexityEstimator
 * Estimates the computational "hardness" of a request to inform quality scoring.
 */
final class RequestComplexityEstimator
{
    /**
     * Estimate complexity on a scale of 1-4.
     */
    public function estimate(CortexRequestData $request): int
    {
        $inputString = is_string($request->input) ? $request->input : json_encode($request->input);
        $length = mb_strlen((string) $inputString);

        // Basic size base score
        $sizeScore = match (true) {
            $length < 500 => 1,
            $length < 2000 => 2,
            $length < 5000 => 3,
            default => 4,
        };

        // Task complexity multiplier
        $taskWeight = match ($request->taskType) {
            AITaskType::ANALYZE_PROPERTY,
            AITaskType::RECOMMEND_NEXT_ACTIONS => 1, // Strategic tasks are complex
            default => 0,
        };

        return min(4, $sizeScore + $taskWeight);
    }
}
