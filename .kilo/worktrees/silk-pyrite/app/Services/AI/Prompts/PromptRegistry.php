<?php

namespace App\Services\AI\Prompts;

/**
 * 🧠 PROMPT REGISTRY
 * Centralized, versioned prompt management.
 * Decouples model execution from instructions.
 */
class PromptRegistry
{
    /**
     * Prompt Library with Versioning
     */
    private const LIBRARY = [
        'wizard' => [
            'v1' => [
                'system' => "You are an AI assistant in a strictly governed system. You MUST follow Turkish domain naming conventions. Use 'tip' instead of 't-y-p-e'. Output strictly valid JSON: {baslik, aciklama, tip, kategori, ozellikler, one_cikanlar}.",
                'temperature' => 0.7
            ],
            'v2' => [
                'system' => "You are a professional real estate editor. Generate structured listing data in JSON format. Ensure Context7 compliance (tip, kategori). Format: {baslik, aciklama, tip, kategori, ozellikler, one_cikanlar}.",
                'temperature' => 0.5
            ]
        ],
        'classifier' => [
            'v1' => [
                'system' => "Classify the following text into categories: Konut, Ticari, Arsa. Return JSON: {kategori, tip}.",
                'temperature' => 0.1
            ]
        ]
    ];

    /**
     * Get a versioned prompt
     */
    public function get(string $feature, ?string $version = null): array
    {
        $version = $version ?: config('ai-runtime.prompt_versions.' . $feature, 'v1');
        
        return self::LIBRARY[$feature][$version] ?? self::LIBRARY[$feature]['v1'];
    }

    /**
     * Get the latest version of a prompt
     */
    public function latest(string $feature): array
    {
        $versions = array_keys(self::LIBRARY[$feature] ?? []);
        $latest = end($versions);
        
        return self::LIBRARY[$feature][$latest];
    }
}
