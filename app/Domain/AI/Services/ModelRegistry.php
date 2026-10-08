<?php

namespace App\Domain\AI\Services;

use App\Domain\AI\Enums\AIProvider;
use App\Enums\AI\ClaudeModel;
use App\Enums\AI\DeepSeekModel;
use App\Enums\AI\GeminiModel;
use App\Enums\AI\OllamaModel;
use App\Enums\AI\OpenAIModel;
use InvalidArgumentException;

/**
 * AI Model Registry Service
 * 
 * Provides centralized model discovery and provider-model mapping.
 * Replaces hardcoded model strings with typed enum references.
 * 
 * Usage:
 *   ModelRegistry::forProvider(AIProvider::OPENAI)->chatModels()
 *   ModelRegistry::defaultFor(AIProvider::DEEPSEEK)
 */
class ModelRegistry
{
    /**
     * Get model enum class for a provider.
     */
    public static function enumFor(AIProvider $provider): string
    {
        return match ($provider) {
            AIProvider::OPENAI => OpenAIModel::class,
            AIProvider::CLAUDE => ClaudeModel::class,
            AIProvider::GEMINI => GeminiModel::class,
            AIProvider::DEEPSEEK => DeepSeekModel::class,
            AIProvider::OLLAMA => OllamaModel::class,
        };
    }

    /**
     * Get default model for a provider.
     */
    public static function defaultFor(AIProvider $provider): string
    {
        return match ($provider) {
            AIProvider::OPENAI => OpenAIModel::defaultChat()->value,
            AIProvider::CLAUDE => ClaudeModel::default()->value,
            AIProvider::GEMINI => GeminiModel::default()->value,
            AIProvider::DEEPSEEK => DeepSeekModel::CHAT->value,
            AIProvider::OLLAMA => OllamaModel::default()->value,
        };
    }

    /**
     * Validate a model string against a provider.
     */
    public static function validate(AIProvider $provider, string $modelValue): bool
    {
        $enumClass = self::enumFor($provider);
        
        return in_array($modelValue, $enumClass::validApiValues(), true);
    }

    /**
     * Resolve a model string to a typed enum case (if possible).
     */
    public static function resolve(AIProvider $provider, string $modelValue): ?object
    {
        $enumClass = self::enumFor($provider);
        
        foreach ($enumClass::cases() as $case) {
            if ($case->value === $modelValue) {
                return $case;
            }
        }
        
        return null;
    }

    /**
     * Get label for a model value.
     */
    public static function label(AIProvider $provider, string $modelValue): string
    {
        $case = self::resolve($provider, $modelValue);
        
        return $case?->label() ?? $modelValue;
    }

    /**
     * Get all models for a provider as options array.
     */
    public static function optionsFor(AIProvider $provider): array
    {
        $enumClass = self::enumFor($provider);
        
        return $enumClass::options();
    }

    /**
     * Get all models grouped by provider.
     */
    public static function allModels(): array
    {
        $result = [];
        
        foreach (AIProvider::cases() as $provider) {
            $result[$provider->value] = [
                'provider' => $provider->value,
                'label' => $provider->name,
                'default' => self::defaultFor($provider),
                'models' => self::optionsFor($provider),
            ];
        }
        
        return $result;
    }

    /**
     * Get chat-capable models for a provider.
     */
    public static function chatModelsFor(AIProvider $provider): array
    {
        $enumClass = self::enumFor($provider);
        
        return array_values(array_filter(
            $enumClass::options(),
            fn ($model) => ($model['category'] ?? $model['tier'] ?? null) !== null
        ));
    }

    /**
     * Get reasoning models for a provider.
     */
    public static function reasoningModelsFor(AIProvider $provider): array
    {
        $enumClass = self::enumFor($provider);
        
        // Check if enum has isReasoning method
        if (method_exists($enumClass, 'byCategory')) {
            return array_map(
                fn ($model) => ['value' => $model->value, 'label' => $model->label()],
                $enumClass::byCategory('reasoning')
            );
        }
        
        return [];
    }

    /**
     * Get model count for a provider.
     */
    public static function countFor(AIProvider $provider): int
    {
        $enumClass = self::enumFor($provider);
        
        return count($enumClass::validApiValues());
    }

    /**
     * Get providers that support a specific capability.
     */
    public static function providersWithCapability(string $capability): array
    {
        return match ($capability) {
            'vision' => [
                AIProvider::OPENAI,
                AIProvider::CLAUDE,
                AIProvider::GEMINI,
            ],
            'reasoning' => [
                AIProvider::OPENAI,
                AIProvider::DEEPSEEK,
                AIProvider::OLLAMA,
            ],
            'local' => [
                AIProvider::OLLAMA,
            ],
            'streaming' => AIProvider::cases(),
            default => [],
        };
    }
}
