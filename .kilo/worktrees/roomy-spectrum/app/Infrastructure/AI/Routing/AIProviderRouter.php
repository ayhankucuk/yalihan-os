<?php

namespace App\Infrastructure\AI\Routing;

use App\Application\AI\DTOs\CortexRequestData;
use App\Domain\AI\Contracts\AIProviderRouterInterface;
use App\Domain\AI\Enums\AIProvider;
use App\Domain\AI\ValueObjects\RoutingDecision;

/**
 * 🛡️ AIProviderRouter
 * The primary decision layer that ranks providers using a weighted scoring model.
 */
final class AIProviderRouter implements AIProviderRouterInterface
{
    public function __construct(
        private readonly ProviderScorer $scorer,
    ) {}

    public function decide(CortexRequestData $request): RoutingDecision
    {
        $providers = [
            AIProvider::DEEPSEEK,
            AIProvider::GEMINI,
            AIProvider::OPENAI,
            AIProvider::OLLAMA,
        ];

        // Step 1: Score all providers
        // Step 2: Sort by total score descending
        $scores = collect($providers)
            ->map(fn (AIProvider $provider) => $this->scorer->score($provider, $request))
            ->sortByDesc(fn ($score) => $score->totalScore)
            ->values();

        $selected = $scores->first();

        return new RoutingDecision(
            selectedProvider: $selected->provider,
            rankedProviders: $scores->all(),
            reason: 'highest_score',
            fallbackUsed: false,
        );
    }
}
