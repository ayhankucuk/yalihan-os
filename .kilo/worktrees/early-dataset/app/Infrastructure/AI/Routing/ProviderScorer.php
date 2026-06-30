<?php

namespace App\Infrastructure\AI\Routing;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\AI\Support\CostEstimator;
use App\Application\AI\Support\ProviderLatencyRepository;
use App\Application\AI\Support\ProviderReliabilityRepository;
use App\Application\AI\Support\RequestComplexityEstimator;
use App\Domain\AI\Enums\AIProvider;
use App\Domain\AI\Enums\AITaskType;
use App\Domain\AI\ValueObjects\ProviderScore;

/**
 * 🛡️ ProviderScorer
 * Calculates the weighted score for a provider based on task-specific and real-time metrics.
 */
final class ProviderScorer
{
    public function __construct(
        private readonly RequestComplexityEstimator $complexityEstimator,
        private readonly CostEstimator $costEstimator,
        private readonly ProviderLatencyRepository $latencyRepository,
        private readonly ProviderReliabilityRepository $reliabilityRepository,
    ) {}

    public function score(AIProvider $provider, CortexRequestData $request): ProviderScore
    {
        $taskFit = $this->taskFitScore($provider, $request->taskType);
        $costScore = $this->costEstimator->score($provider, $request);
        $latencyScore = $this->latencyRepository->score($provider);
        $reliabilityScore = $this->reliabilityRepository->score($provider);
        $qualityScore = $this->qualityScore($provider, $request);

        // Weighted Formula (100pt base)
        $total =
            ($taskFit * 0.35) +
            ($costScore * 0.25) +
            ($latencyScore * 0.20) +
            ($reliabilityScore * 0.15) +
            ($qualityScore * 0.05);

        return new ProviderScore(
            provider: $provider,
            totalScore: round($total, 2),
            taskFit: $taskFit,
            costScore: $costScore,
            latencyScore: $latencyScore,
            reliabilityScore: $reliabilityScore,
            qualityScore: $qualityScore,
        );
    }

    private function taskFitScore(AIProvider $provider, AITaskType $taskType): float
    {
        return match ($taskType) {
            AITaskType::EXTRACT_PROPERTY_FEATURES => match ($provider) {
                AIProvider::DEEPSEEK => 95,
                AIProvider::GEMINI => 70,
                AIProvider::OPENAI => 68,
                AIProvider::OLLAMA => 45,
                default => 50,
            },
            AITaskType::SUGGEST_PROPERTY_TEMPLATE => match ($provider) {
                AIProvider::DEEPSEEK => 92,
                AIProvider::GEMINI => 74,
                AIProvider::OPENAI => 72,
                AIProvider::OLLAMA => 48,
                default => 50,
            },
            AITaskType::GENERATE_PROPERTY_TEMPLATE => match ($provider) {
                AIProvider::DEEPSEEK => 85,
                AIProvider::GEMINI => 82,
                AIProvider::OPENAI => 78,
                AIProvider::OLLAMA => 50,
                default => 50,
            },
            AITaskType::ANALYZE_PROPERTY,
            AITaskType::RECOMMEND_NEXT_ACTIONS => match ($provider) {
                AIProvider::GEMINI => 92,
                AIProvider::OPENAI => 88,
                AIProvider::DEEPSEEK => 72, // Reasoner
                AIProvider::OLLAMA => 52,
                default => 50,
            },
            default => 50,
        };
    }

    private function qualityScore(AIProvider $provider, CortexRequestData $request): float
    {
        $complexity = $this->complexityEstimator->estimate($request);

        return match ($provider) {
            AIProvider::GEMINI => $complexity >= 3 ? 90 : 78,
            AIProvider::OPENAI => $complexity >= 3 ? 88 : 76,
            AIProvider::DEEPSEEK => $complexity <= 2 ? 84 : 70,
            AIProvider::OLLAMA => $complexity <= 2 ? 68 : 50,
            default => 60,
        };
    }
}
