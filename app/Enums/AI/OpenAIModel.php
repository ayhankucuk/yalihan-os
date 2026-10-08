<?php

namespace App\Enums\AI;

/**
 * OpenAI Model Enum
 * 
 * Supported OpenAI models for chat, completion, and audio tasks.
 * API reference: https://platform.openai.com/docs/models
 */
enum OpenAIModel: string
{
    // Chat Models
    case GPT_4O = 'gpt-4o';
    case GPT_4O_MINI = 'gpt-4o-mini';
    case GPT_4_TURBO = 'gpt-4-turbo';
    case GPT_4 = 'gpt-4';
    case GPT_4O_LATEST = 'gpt-4o-latest';
    
    // Legacy / Deprecated
    case GPT_35_TURBO = 'gpt-3.5-turbo';
    case GPT_35_TURBO_16K = 'gpt-3.5-turbo-16k';
    
    // Reasoning Models (O1/O3)
    case O1_PREVIEW = 'o1-preview';
    case O1_MINI = 'o1-mini';
    case O3_MINI = 'o3-mini';
    case O3_MINI_MEDIUM = 'o3-mini-medium';
    
    // Audio Models
    case WHISPER_1 = 'whisper-1';
    
    // Embedding Models
    case TEXT_EMBEDDING_3_LARGE = 'text-embedding-3-large';
    case TEXT_EMBEDDING_3_SMALL = 'text-embedding-3-small';
    case TEXT_EMBEDDING_ADA_002 = 'text-embedding-ada-002';

    /**
     * Get human-readable label for the model.
     */
    public function label(): string
    {
        return match ($this) {
            // Chat Models
            self::GPT_4O => 'GPT-4o (Latest)',
            self::GPT_4O_MINI => 'GPT-4o Mini (Fast)',
            self::GPT_4O_LATEST => 'GPT-4o Latest',
            self::GPT_4_TURBO => 'GPT-4 Turbo',
            self::GPT_4 => 'GPT-4',
            
            // Legacy
            self::GPT_35_TURBO => 'GPT-3.5 Turbo [Legacy]',
            self::GPT_35_TURBO_16K => 'GPT-3.5 Turbo 16K [Legacy]',
            
            // Reasoning
            self::O1_PREVIEW => 'O1 Preview (Reasoning)',
            self::O1_MINI => 'O1 Mini (Reasoning)',
            self::O3_MINI => 'O3 Mini (Reasoning)',
            self::O3_MINI_MEDIUM => 'O3 Mini Medium (Reasoning)',
            
            // Audio
            self::WHISPER_1 => 'Whisper-1 (Speech-to-Text)',
            
            // Embeddings
            self::TEXT_EMBEDDING_3_LARGE => 'Text Embedding 3 Large',
            self::TEXT_EMBEDDING_3_SMALL => 'Text Embedding 3 Small',
            self::TEXT_EMBEDDING_ADA_002 => 'Text Embedding Ada 002 [Legacy]',
        };
    }

    /**
     * Get the category of this model.
     */
    public function category(): string
    {
        return match ($this) {
            self::GPT_4O, self::GPT_4O_MINI, self::GPT_4O_LATEST,
            self::GPT_4_TURBO, self::GPT_4,
            self::GPT_35_TURBO, self::GPT_35_TURBO_16K => 'chat',
            
            self::O1_PREVIEW, self::O1_MINI,
            self::O3_MINI, self::O3_MINI_MEDIUM => 'reasoning',
            
            self::WHISPER_1 => 'audio',
            
            self::TEXT_EMBEDDING_3_LARGE,
            self::TEXT_EMBEDDING_3_SMALL,
            self::TEXT_EMBEDDING_ADA_002 => 'embedding',
        };
    }

    /**
     * Check if this model is a reasoning model.
     */
    public function isReasoning(): bool
    {
        return $this->category() === 'reasoning';
    }

    /**
     * Check if this model is deprecated.
     */
    public function isDeprecated(): bool
    {
        return match ($this) {
            self::GPT_35_TURBO, self::GPT_35_TURBO_16K,
            self::TEXT_EMBEDDING_ADA_002 => true,
            default => false,
        };
    }

    /**
     * Get all model values as array.
     */
    public static function values(): array
    {
        return array_map(fn (self $model) => $model->value, self::cases());
    }

    /**
     * Get valid API values (non-deprecated).
     */
    public static function validApiValues(): array
    {
        return array_map(
            fn (self $model) => $model->value,
            array_filter(self::cases(), fn (self $model) => ! $model->isDeprecated())
        );
    }

    /**
     * Get options for forms (value => label).
     */
    public static function options(): array
    {
        return array_map(
            fn (self $model) => [
                'value' => $model->value,
                'label' => $model->label(),
                'category' => $model->category(),
                'deprecated' => $model->isDeprecated(),
            ],
            self::cases()
        );
    }

    /**
     * Get models by category.
     */
    public static function byCategory(string $category): array
    {
        return array_map(
            fn (self $model) => $model,
            array_filter(self::cases(), fn (self $model) => $model->category() === $category)
        );
    }

    /**
     * Get the default chat model.
     */
    public static function defaultChat(): self
    {
        return self::GPT_4O;
    }

    /**
     * Get the default audio model.
     */
    public static function defaultAudio(): self
    {
        return self::WHISPER_1;
    }
}
