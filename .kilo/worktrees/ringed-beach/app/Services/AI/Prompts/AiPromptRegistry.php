<?php

namespace App\Services\AI\Prompts;

/**
 * 🧠 AiPromptRegistry
 * Managed prompt versions for Yalihan AI.
 */
class AiPromptRegistry
{
    /**
     * Get prompt by purpose and version.
     * 
     * @throws \InvalidArgumentException
     */
    public function get(string $purpose, ?string $version = null): string
    {
        $version = $version ?: config('ai.prompt_version', 'v1');
        $prompt = config("ai-prompts.{$purpose}.{$version}");

        if (!$prompt) {
            throw new \InvalidArgumentException("AI Prompt Version Missing: [{$purpose}@{$version}]");
        }

        return $prompt;
    }
}
