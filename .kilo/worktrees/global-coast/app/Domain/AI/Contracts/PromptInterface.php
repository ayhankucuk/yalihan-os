<?php

namespace App\Domain\AI\Contracts;

/**
 * ️ PromptInterface
 * 
 * Defines the contract for an AI prompt structure.
 * This decouples prompt logic from the provider (transport) layer.
 */
interface PromptInterface
{
    /**
     * Get the system instructions for the AI.
     */
    public function getSystemInstructions(): string;

    /**
     * Get the user prompt content.
     */
    public function getUserPrompt(): string;

    /**
     * Get the expected JSON schema (if applicable).
     */
    public function getJSONSchema(): ?array;

    /**
     * Get any additional provider-specific configuration.
     */
    public function getOptions(): array;
}
