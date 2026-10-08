<?php

namespace App\Enums\AI;

/**
 * Provider Model Enum
 * 
 * Unified enum for all AI provider models in provider:model format.
 * Examples: 'openai:gpt-4o', 'claude:claude-3-5-sonnet', 'deepseek:deepseek-chat'
 */
enum ProviderModel: string
{
    // OpenAI Models
    case GPT_4O = 'openai:gpt-4o';
    case GPT_4O_MINI = 'openai:gpt-4o-mini';
    case GPT_4O_LATEST = 'openai:gpt-4o-latest';
    case GPT_4_TURBO = 'openai:gpt-4-turbo';
    case GPT_4 = 'openai:gpt-4';
    case GPT_35_TURBO = 'openai:gpt-3.5-turbo';
    case O1_PREVIEW = 'openai:o1-preview';
    case O1_MINI = 'openai:o1-mini';
    case O3_MINI = 'openai:o3-mini';
    case WHISPER_1 = 'openai:whisper-1';
    case TEXT_EMBEDDING_3_LARGE = 'openai:text-embedding-3-large';
    case TEXT_EMBEDDING_3_SMALL = 'openai:text-embedding-3-small';

    // Claude Models (Anthropic)
    case CLAUDE_OPUS_4 = 'claude:claude-opus-4-5';
    case CLAUDE_SONNET_4 = 'claude:claude-sonnet-4-5';
    case CLAUDE_HAIKU_4 = 'claude:claude-haiku-4-5';
    case CLAUDE_SONNET_3_5 = 'claude:claude-3-5-sonnet';
    case CLAUDE_HAIKU_3_5 = 'claude:claude-3-5-haiku';
    case CLAUDE_OPUS_3 = 'claude:claude-3-opus';
    case CLAUDE_SONNET_3 = 'claude:claude-3-sonnet';
    case CLAUDE_HAIKU_3 = 'claude:claude-3-haiku';
    case CLAUDE_INSTANT = 'claude:claude-instant';

    // Gemini Models (Google)
    case GEMINI_2_0_FLASH = 'gemini:gemini-2.0-flash';
    case GEMINI_2_0_FLASH_LITE = 'gemini:gemini-2.0-flash-lite';
    case GEMINI_2_0_PRO = 'gemini:gemini-2.0-pro';
    case GEMINI_1_5_FLASH = 'gemini:gemini-1.5-flash';
    case GEMINI_1_5_FLASH_8B = 'gemini:gemini-1.5-flash-8b';
    case GEMINI_1_5_PRO = 'gemini:gemini-1.5-pro';
    case GEMINI_PRO = 'gemini:gemini-pro';

    // DeepSeek Models
    case DEEPSEEK_CHAT = 'deepseek:deepseek-chat';
    case DEEPSEEK_REASONER = 'deepseek:deepseek-reasoner';
    case DEEPSEEK_V3 = 'deepseek:deepseek-v3';

    // Ollama Models (Local)
    case OLLAMA_QWEN_3_5_9B = 'ollama:qwen3.5:9b';
    case OLLAMA_QWEN_3_5_14B = 'ollama:qwen3.5:14b';
    case OLLAMA_QWEN_3_5_32B = 'ollama:qwen3.5:32b';
    case OLLAMA_LLAMA_3_1_8B = 'ollama:llama3.1:8b';
    case OLLAMA_LLAMA_3_1_70B = 'ollama:llama3.1:70b';
    case OLLAMA_LLAMA_3_2_1B = 'ollama:llama3.2:1b';
    case OLLAMA_LLAMA_3_2_3B = 'ollama:llama3.2:3b';
    case OLLAMA_PHI_3_5 = 'ollama:phi3.5:latest';
    case OLLAMA_DEEPSEEK_R1_7B = 'ollama:deepseek-r1:7b';
    case OLLAMA_DEEPSEEK_R1_14B = 'ollama:deepseek-r1:14b';
    case OLLAMA_DEEPSEEK_R1_32B = 'ollama:deepseek-r1:32b';
    case OLLAMA_MISTRAL_7B = 'ollama:mistral:7b';
    case OLLAMA_GEMMA_2_9B = 'ollama:gemma2:9b';
    case OLLAMA_NOMIC_EMBED = 'ollama:nomic-embed-text';

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Get the provider part (e.g., 'openai', 'claude').
     */
    public function provider(): string
    {
        return explode(':', $this->value)[0];
    }

    /**
     * Get the model part (e.g., 'gpt-4o', 'claude-3-5-sonnet').
     */
    public function model(): string
    {
        $parts = explode(':', $this->value);
        return $parts[1] ?? $this->value;
    }

    /**
     * Get all models for a specific provider.
     *
     * @param string $provider e.g., 'openai', 'claude', 'gemini'
     * @return self[]
     */
    public static function forProvider(string $provider): array
    {
        return array_filter(
            self::cases(),
            fn (self $case) => $case->provider() === $provider
        );
    }

    /**
     * Get all models grouped by provider.
     *
     * @return array<string, self[]>
     */
    public static function byProvider(): array
    {
        $result = [];
        
        foreach (self::cases() as $case) {
            $provider = $case->provider();
            if (!isset($result[$provider])) {
                $result[$provider] = [];
            }
            $result[$provider][] = $case;
        }
        
        return $result;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            // OpenAI
            self::GPT_4O => 'GPT-4o',
            self::GPT_4O_MINI => 'GPT-4o Mini',
            self::GPT_4O_LATEST => 'GPT-4o Latest',
            self::GPT_4_TURBO => 'GPT-4 Turbo',
            self::GPT_4 => 'GPT-4',
            self::GPT_35_TURBO => 'GPT-3.5 Turbo',
            self::O1_PREVIEW => 'O1 Preview',
            self::O1_MINI => 'O1 Mini',
            self::O3_MINI => 'O3 Mini',
            self::WHISPER_1 => 'Whisper-1',
            self::TEXT_EMBEDDING_3_LARGE => 'Text Embedding 3 Large',
            self::TEXT_EMBEDDING_3_SMALL => 'Text Embedding 3 Small',

            // Claude
            self::CLAUDE_OPUS_4 => 'Claude Opus 4',
            self::CLAUDE_SONNET_4 => 'Claude Sonnet 4',
            self::CLAUDE_HAIKU_4 => 'Claude Haiku 4',
            self::CLAUDE_SONNET_3_5 => 'Claude Sonnet 3.5',
            self::CLAUDE_HAIKU_3_5 => 'Claude Haiku 3.5',
            self::CLAUDE_OPUS_3 => 'Claude Opus 3',
            self::CLAUDE_SONNET_3 => 'Claude Sonnet 3',
            self::CLAUDE_HAIKU_3 => 'Claude Haiku 3',
            self::CLAUDE_INSTANT => 'Claude Instant',

            // Gemini
            self::GEMINI_2_0_FLASH => 'Gemini 2.0 Flash',
            self::GEMINI_2_0_FLASH_LITE => 'Gemini 2.0 Flash Lite',
            self::GEMINI_2_0_PRO => 'Gemini 2.0 Pro',
            self::GEMINI_1_5_FLASH => 'Gemini 1.5 Flash',
            self::GEMINI_1_5_FLASH_8B => 'Gemini 1.5 Flash 8B',
            self::GEMINI_1_5_PRO => 'Gemini 1.5 Pro',
            self::GEMINI_PRO => 'Gemini Pro',

            // DeepSeek
            self::DEEPSEEK_CHAT => 'DeepSeek V3 (Chat)',
            self::DEEPSEEK_REASONER => 'DeepSeek R1 (Reasoner)',
            self::DEEPSEEK_V3 => 'DeepSeek V3',

            // Ollama
            self::OLLAMA_QWEN_3_5_9B => 'Qwen 3.5 9B',
            self::OLLAMA_QWEN_3_5_14B => 'Qwen 3.5 14B',
            self::OLLAMA_QWEN_3_5_32B => 'Qwen 3.5 32B',
            self::OLLAMA_LLAMA_3_1_8B => 'Llama 3.1 8B',
            self::OLLAMA_LLAMA_3_1_70B => 'Llama 3.1 70B',
            self::OLLAMA_LLAMA_3_2_1B => 'Llama 3.2 1B',
            self::OLLAMA_LLAMA_3_2_3B => 'Llama 3.2 3B',
            self::OLLAMA_PHI_3_5 => 'Phi 3.5',
            self::OLLAMA_DEEPSEEK_R1_7B => 'DeepSeek R1 7B',
            self::OLLAMA_DEEPSEEK_R1_14B => 'DeepSeek R1 14B',
            self::OLLAMA_DEEPSEEK_R1_32B => 'DeepSeek R1 32B',
            self::OLLAMA_MISTRAL_7B => 'Mistral 7B',
            self::OLLAMA_GEMMA_2_9B => 'Gemma 2 9B',
            self::OLLAMA_NOMIC_EMBED => 'Nomic Embed Text',
        };
    }

    /**
     * Get full label with provider.
     */
    public function fullLabel(): string
    {
        return ucfirst($this->provider()) . ' / ' . $this->label();
    }

    /**
     * Get all values as array.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Get options for forms.
     *
     * @return array<int, array{value: string, label: string, provider: string, model: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->fullLabel(),
                'provider' => $case->provider(),
                'model' => $case->model(),
            ],
            self::cases()
        );
    }

    /**
     * Get options grouped by provider.
     *
     * @return array<string, array<int, array{value: string, label: string, provider: string, model: string}>>
     */
    public static function optionsByProvider(): array
    {
        $result = [];
        
        foreach (self::cases() as $case) {
            $provider = $case->provider();
            if (!isset($result[$provider])) {
                $result[$provider] = [];
            }
            $result[$provider][] = [
                'value' => $case->value,
                'label' => $case->label(),
                'provider' => $provider,
                'model' => $case->model(),
            ];
        }
        
        return $result;
    }

    /**
     * Find by value or return null.
     */
    public static function find(string $value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
        return null;
    }

    /**
     * Get default model for a provider.
     */
    public static function defaultFor(string $provider): ?self
    {
        return match ($provider) {
            'openai' => self::GPT_4O,
            'claude' => self::CLAUDE_SONNET_3_5,
            'gemini' => self::GEMINI_2_0_FLASH,
            'deepseek' => self::DEEPSEEK_CHAT,
            'ollama' => self::OLLAMA_QWEN_3_5_9B,
            default => null,
        };
    }

    /**
     * Get available providers.
     *
     * @return string[]
     */
    public static function providers(): array
    {
        return array_unique(array_map(
            fn (self $case) => $case->provider(),
            self::cases()
        ));
    }
}
