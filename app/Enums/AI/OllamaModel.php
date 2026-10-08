<?php

namespace App\Enums\AI;

/**
 * Ollama Model Enum
 * 
 * Supported Ollama local models for self-hosted inference.
 * API reference: https://github.com/ollama/ollama
 * 
 * Note: These are popular Ollama models. Custom models can be added
 * via environment variables (OLLAMA_MODEL).
 */
enum OllamaModel: string
{
    // Qwen Series (Recommended for YALIHAN)
    case QWEN_3_5_9B = 'qwen3.5:9b';
    case QWEN_3_5_14B = 'qwen3.5:14b';
    case QWEN_3_5_32B = 'qwen3.5:32b';
    case QWEN_2_5_72B = 'qwen2.5:72b';
    
    // Llama 3 Series
    case LLAMA_3_1_8B = 'llama3.1:8b';
    case LLAMA_3_1_70B = 'llama3.1:70b';
    case LLAMA_3_2_1B = 'llama3.2:1b';
    case LLAMA_3_2_3B = 'llama3.2:3b';
    case LLAMA_3_8B = 'llama3:8b';
    case LLAMA_3_70B = 'llama3:70b';
    
    // Phi Series (Microsoft - Small models)
    case PHI_3_5_MINI = 'phi3.5:latest';
    case PHI_3_MEDIUM = 'phi3:medium';
    
    // DeepSeek Series
    case DEEPSEEK_V3 = 'deepseek-v3';
    case DEEPSEEK_R1_7B = 'deepseek-r1:7b';
    case DEEPSEEK_R1_14B = 'deepseek-r1:14b';
    case DEEPSEEK_R1_32B = 'deepseek-r1:32b';
    case DEEPSEEK_R1_70B = 'deepseek-r1:70b';
    
    // Mistral Series
    case MISTRAL_7B = 'mistral:7b';
    case MIXTRAL_8X7B = 'mixtral:8x7b';
    
    // Gemma Series (Google)
    case GEMMA_2_2B = 'gemma2:2b';
    case GEMMA_2_9B = 'gemma2:9b';
    case GEMMA_2_27B = 'gemma2:27b';
    
    // Command R (Cohere)
    case COMMAND_R = 'command-r';
    case COMMAND_R_PLUS = 'command-r-plus';
    
    // Nomic Embeddings
    case NOMIC_EMBED_TEXT = 'nomic-embed-text';

    /**
     * Get human-readable label for the model.
     */
    public function label(): string
    {
        return match ($this) {
            // Qwen
            self::QWEN_3_5_9B => 'Qwen 3.5 9B (Recommended)',
            self::QWEN_3_5_14B => 'Qwen 3.5 14B',
            self::QWEN_3_5_32B => 'Qwen 3.5 32B',
            self::QWEN_2_5_72B => 'Qwen 2.5 72B',
            
            // Llama
            self::LLAMA_3_1_8B => 'Llama 3.1 8B',
            self::LLAMA_3_1_70B => 'Llama 3.1 70B',
            self::LLAMA_3_2_1B => 'Llama 3.2 1B (Ultra Light)',
            self::LLAMA_3_2_3B => 'Llama 3.2 3B (Light)',
            self::LLAMA_3_8B => 'Llama 3 8B',
            self::LLAMA_3_70B => 'Llama 3 70B',
            
            // Phi
            self::PHI_3_5_MINI => 'Phi 3.5 Mini (Fast)',
            self::PHI_3_MEDIUM => 'Phi 3 Medium',
            
            // DeepSeek
            self::DEEPSEEK_V3 => 'DeepSeek V3',
            self::DEEPSEEK_R1_7B => 'DeepSeek R1 7B (Reasoning)',
            self::DEEPSEEK_R1_14B => 'DeepSeek R1 14B (Reasoning)',
            self::DEEPSEEK_R1_32B => 'DeepSeek R1 32B (Reasoning)',
            self::DEEPSEEK_R1_70B => 'DeepSeek R1 70B (Reasoning)',
            
            // Mistral
            self::MISTRAL_7B => 'Mistral 7B',
            self::MIXTRAL_8X7B => 'Mixtral 8x7B (MoE)',
            
            // Gemma
            self::GEMMA_2_2B => 'Gemma 2 2B',
            self::GEMMA_2_9B => 'Gemma 2 9B',
            self::GEMMA_2_27B => 'Gemma 2 27B',
            
            // Command R
            self::COMMAND_R => 'Command R',
            self::COMMAND_R_PLUS => 'Command R+',
            
            // Embeddings
            self::NOMIC_EMBED_TEXT => 'Nomic Embed Text (Embeddings)',
        };
    }

    /**
     * Get the family/series of this model.
     */
    public function family(): string
    {
        return match ($this) {
            self::QWEN_3_5_9B, self::QWEN_3_5_14B,
            self::QWEN_3_5_32B, self::QWEN_2_5_72B => 'qwen',
            
            self::LLAMA_3_1_8B, self::LLAMA_3_1_70B,
            self::LLAMA_3_2_1B, self::LLAMA_3_2_3B,
            self::LLAMA_3_8B, self::LLAMA_3_70B => 'llama',
            
            self::PHI_3_5_MINI, self::PHI_3_MEDIUM => 'phi',
            
            self::DEEPSEEK_V3, self::DEEPSEEK_R1_7B,
            self::DEEPSEEK_R1_14B, self::DEEPSEEK_R1_32B,
            self::DEEPSEEK_R1_70B => 'deepseek',
            
            self::MISTRAL_7B, self::MIXTRAL_8X7B => 'mistral',
            
            self::GEMMA_2_2B, self::GEMMA_2_9B,
            self::GEMMA_2_27B => 'gemma',
            
            self::COMMAND_R, self::COMMAND_R_PLUS => 'command',
            
            self::NOMIC_EMBED_TEXT => 'nomic',
        };
    }

    /**
     * Get approximate parameter count.
     */
    public function parameters(): string
    {
        return match ($this) {
            self::QWEN_3_5_9B => '9B',
            self::QWEN_3_5_14B => '14B',
            self::QWEN_3_5_32B => '32B',
            self::QWEN_2_5_72B => '72B',
            
            self::LLAMA_3_1_8B, self::LLAMA_3_8B => '8B',
            self::LLAMA_3_1_70B, self::LLAMA_3_70B => '70B',
            self::LLAMA_3_2_1B => '1B',
            self::LLAMA_3_2_3B => '3B',
            
            self::PHI_3_5_MINI => '3.5B',
            self::PHI_3_MEDIUM => '14B',
            
            self::DEEPSEEK_V3 => '236B',
            self::DEEPSEEK_R1_7B => '7B',
            self::DEEPSEEK_R1_14B => '14B',
            self::DEEPSEEK_R1_32B => '32B',
            self::DEEPSEEK_R1_70B => '70B',
            
            self::MISTRAL_7B => '7B',
            self::MIXTRAL_8X7B => '8x7B (MoE)',
            
            self::GEMMA_2_2B => '2B',
            self::GEMMA_2_9B => '9B',
            self::GEMMA_2_27B => '27B',
            
            self::COMMAND_R => '35B',
            self::COMMAND_R_PLUS => '104B',
            
            self::NOMIC_EMBED_TEXT => 'Embedding',
        };
    }

    /**
     * Check if this model supports reasoning (R1 series).
     */
    public function isReasoningModel(): bool
    {
        return str_contains($this->value, '-r1');
    }

    /**
     * Check if this is an embedding model.
     */
    public function isEmbeddingModel(): bool
    {
        return $this->family() === 'nomic';
    }

    /**
     * Check if this model is recommended for YALIHAN use.
     */
    public function isRecommended(): bool
    {
        return match ($this) {
            self::QWEN_3_5_9B, self::QWEN_3_5_14B => true,
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
     * Get valid API values.
     */
    public static function validApiValues(): array
    {
        return self::values();
    }

    /**
     * Get options for forms.
     */
    public static function options(): array
    {
        return array_map(
            fn (self $model) => [
                'value' => $model->value,
                'label' => $model->label(),
                'family' => $model->family(),
                'parameters' => $model->parameters(),
                'reasoning' => $model->isReasoningModel(),
                'embedding' => $model->isEmbeddingModel(),
                'recommended' => $model->isRecommended(),
            ],
            self::cases()
        );
    }

    /**
     * Get the default model.
     */
    public static function default(): self
    {
        return self::QWEN_3_5_9B;
    }

    /**
     * Get models by family.
     */
    public static function byFamily(string $family): array
    {
        return array_filter(
            self::cases(),
            fn (self $model) => $model->family() === $family
        );
    }

    /**
     * Get recommended models only.
     */
    public static function recommended(): array
    {
        return array_filter(
            self::cases(),
            fn (self $model) => $model->isRecommended()
        );
    }
}
