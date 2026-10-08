<?php

namespace App\Enums\AI;

/**
 * Gemini Model Enum
 * 
 * Supported Google Gemini models for chat, reasoning, and multimodal tasks.
 * API reference: https://ai.google.dev/gemini-api/docs/models
 */
enum GeminiModel: string
{
    // Gemini 2.0 Series (Latest)
    case GEMINI_2_0_FLASH = 'gemini-2.0-flash';
    case GEMINI_2_0_FLASH_LITE = 'gemini-2.0-flash-lite';
    case GEMINI_2_0_PRO = 'gemini-2.0-pro';
    
    // Gemini 1.5 Series
    case GEMINI_1_5_FLASH = 'gemini-1.5-flash';
    case GEMINI_1_5_FLASH_8B = 'gemini-1.5-flash-8b';
    case GEMINI_1_5_PRO = 'gemini-1.5-pro';
    
    // Gemini 1.0 Series (Legacy)
    case GEMINI_PRO = 'gemini-pro';
    case GEMINI_PRO_VISION = 'gemini-pro-vision';

    /**
     * Get human-readable label for the model.
     */
    public function label(): string
    {
        return match ($this) {
            // Gemini 2.0
            self::GEMINI_2_0_FLASH => 'Gemini 2.0 Flash (Fast)',
            self::GEMINI_2_0_FLASH_LITE => 'Gemini 2.0 Flash Lite (Budget)',
            self::GEMINI_2_0_PRO => 'Gemini 2.0 Pro (Premium)',
            
            // Gemini 1.5
            self::GEMINI_1_5_FLASH => 'Gemini 1.5 Flash',
            self::GEMINI_1_5_FLASH_8B => 'Gemini 1.5 Flash 8B (Fast)',
            self::GEMINI_1_5_PRO => 'Gemini 1.5 Pro (Premium)',
            
            // Legacy
            self::GEMINI_PRO => 'Gemini Pro [Legacy]',
            self::GEMINI_PRO_VISION => 'Gemini Pro Vision [Legacy]',
        };
    }

    /**
     * Get the series of this model.
     */
    public function series(): string
    {
        return match ($this) {
            self::GEMINI_2_0_FLASH, self::GEMINI_2_0_FLASH_LITE,
            self::GEMINI_2_0_PRO => '2.0',
            
            self::GEMINI_1_5_FLASH, self::GEMINI_1_5_FLASH_8B,
            self::GEMINI_1_5_PRO => '1.5',
            
            self::GEMINI_PRO, self::GEMINI_PRO_VISION => '1.0',
        };
    }

    /**
     * Check if this model supports vision (images).
     */
    public function supportsVision(): bool
    {
        return match ($this) {
            self::GEMINI_2_0_FLASH, self::GEMINI_2_0_FLASH_LITE,
            self::GEMINI_2_0_PRO,
            self::GEMINI_1_5_FLASH, self::GEMINI_1_5_FLASH_8B,
            self::GEMINI_1_5_PRO,
            self::GEMINI_PRO_VISION => true,
            default => false,
        };
    }

    /**
     * Check if this model is deprecated.
     */
    public function isDeprecated(): bool
    {
        return $this->series() === '1.0';
    }

    /**
     * Check if this is the latest series.
     */
    public function isLatestSeries(): bool
    {
        return $this->series() === '2.0';
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
                'series' => $model->series(),
                'vision' => $model->supportsVision(),
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
        return self::GEMINI_2_0_FLASH;
    }

    /**
     * Get models by series.
     */
    public static function bySeries(string $series): array
    {
        return array_filter(
            self::cases(),
            fn (self $model) => $model->series() === $series
        );
    }
}
