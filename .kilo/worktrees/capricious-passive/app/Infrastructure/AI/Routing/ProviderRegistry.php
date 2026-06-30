<?php

namespace App\Infrastructure\AI\Routing;

use App\Domain\AI\Contracts\CortexServiceInterface;
use App\Domain\AI\Enums\AIProvider;
use App\Infrastructure\AI\Providers\DeepSeekCortexAdapter;
use App\Infrastructure\AI\Providers\GeminiCortexAdapter;
use App\Infrastructure\AI\Providers\OllamaCortexAdapter;
use App\Infrastructure\AI\Providers\OpenAICortexAdapter;
use InvalidArgumentException;

/**
 * 🛡️ ProviderRegistry
 * Maps AIProvider enums to concrete service adapter instances.
 */
final class ProviderRegistry
{
    public function __construct(
        private readonly OllamaCortexAdapter $ollamaAdapter,
        private readonly OpenAICortexAdapter $openaiAdapter,
        private readonly GeminiCortexAdapter $geminiAdapter,
        private readonly DeepSeekCortexAdapter $deepseekAdapter,
    ) {}

    public function get(AIProvider $provider): CortexServiceInterface
    {
        return match ($provider) {
            AIProvider::OLLAMA => $this->ollamaAdapter,
            AIProvider::OPENAI => $this->openaiAdapter,
            AIProvider::GEMINI => $this->geminiAdapter,
            AIProvider::DEEPSEEK => $this->deepseekAdapter,
            default => throw new InvalidArgumentException("Provider [{$provider->value}] not supported by Registry.")
        };
    }
}
