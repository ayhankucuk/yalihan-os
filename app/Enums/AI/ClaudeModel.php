<?php

namespace App\Enums\AI;

/**
 * Claude Model Enum
 * 
 * Supported Claude models (Anthropic) for chat and reasoning tasks.
 * API reference: https://docs.anthropic.com/en/docs/models-overview
 */
enum ClaudeModel: string
{
    // Claude 4 Series (Latest)
    case CLAUDE_OPUS_4 = 'claude-opus-4-5';
    case CLAUDE_SONNET_4 = 'claude-sonnet-4-5';
    case CLAUDE_HAIKU_4 = 'claude-haiku-4-5';
    
    // Claude 3 Series
    case CLAUDE_OPUS_3 = 'claude-3-opus';
    case CLAUDE_SONNET_3 = 'claude-3-5-sonnet';
    case CLAUDE_HAIKU_3 = 'claude-3-haiku';
    
    // Claude 3.5 Series
    case CLAUDE_SONNET_3_5 = 'claude-3.5-sonnet';
    case CLAUDE_HAIKU_3_5 = 'claude-3.5-haiku';
    
    // Legacy
    case CLAUDE_2 = 'claude-2';
    case CLAUDE_2_1 = 'claude-2.1';
    case CLAUDE_INSTANT = 'claude-instant';

    /**
     * Get human-readable label for the model.
     */
    public function label(): string
    {
        return match ($this) {
            // Claude 4
            self::CLAUDE_OPUS_4 => 'Claude Opus 4 (Premium)',
            self::CLAUDE_SONNET_4 => 'Claude Sonnet 4 (Balanced)',
            self::CLAUDE_HAIKU_4 => 'Claude Haiku 4 (Fast)',
            
            // Claude 3
            self::CLAUDE_OPUS_3 => 'Claude 3 Opus',
            self::CLAUDE_SONNET_3 => 'Claude 3.5 Sonnet',
            self::CLAUDE_HAIKU_3 => 'Claude 3 Haiku',
            
            // Claude 3.5
            self::CLAUDE_SONNET_3_5 => 'Claude 3.5 Sonnet v2',
            self::CLAUDE_HAIKU_3_5 => 'Claude 3.5 Haiku',
            
            // Legacy
            self::CLAUDE_2 => 'Claude 2 [Legacy]',
            self::CLAUDE_2_1 => 'Claude 2.1 [Legacy]',
            self::CLAUDE_INSTANT => 'Claude Instant [Legacy]',
        };
    }

    /**
     * Get the tier of this model.
     */
    public function tier(): string
    {
        return match ($this) {
            self::CLAUDE_OPUS_4, self::CLAUDE_OPUS_3 => 'opus',
            self::CLAUDE_SONNET_4, self::CLAUDE_SONNET_3,
            self::CLAUDE_SONNET_3_5 => 'sonnet',
            self::CLAUDE_HAIKU_4, self::CLAUDE_HAIKU_3,
            self::CLAUDE_HAIKU_3_5 => 'haiku',
            self::CLAUDE_2, self::CLAUDE_2_1,
            self::CLAUDE_INSTANT => 'legacy',
        };
    }

    /**
     * Get the generation (3, 3.5, 4).
     */
    public function generation(): string
    {
        return match ($this) {
            self::CLAUDE_OPUS_4, self::CLAUDE_SONNET_4,
            self::CLAUDE_HAIKU_4 => '4',
            
            self::CLAUDE_OPUS_3, self::CLAUDE_SONNET_3,
            self::CLAUDE_HAIKU_3 => '3',
            
            self::CLAUDE_SONNET_3_5, self::CLAUDE_HAIKU_3_5 => '3.5',
            
            self::CLAUDE_2, self::CLAUDE_2_1,
            self::CLAUDE_INSTANT => '2',
        };
    }

    /**
     * Check if this model is the latest generation.
     */
    public function isLatestGen(): bool
    {
        return $this->generation() === '4';
    }

    /**
     * Check if this model is deprecated.
     */
    public function isDeprecated(): bool
    {
        return $this->tier() === 'legacy';
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
     * Get options for forms.
     */
    public static function options(): array
    {
        return array_map(
            fn (self $model) => [
                'value' => $model->value,
                'label' => $model->label(),
                'tier' => $model->tier(),
                'generation' => $model->generation(),
                'deprecated' => $model->isDeprecated(),
            ],
            self::cases()
        );
    }

    /**
     * Get the default model.
     */
    public static function default(): self
    {
        return self::CLAUDE_SONNET_3_5;
    }

    /**
     * Get models by tier.
     */
    public static function byTier(string $tier): array
    {
        return array_filter(
            self::cases(),
            fn (self $model) => $model->tier() === $tier
        );
    }
}
