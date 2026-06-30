<?php

namespace App\Services\Wizard;

use App\Jobs\AI\ProcessAiTaskJob;
use App\Services\AI\DTO\AIRequest;
use App\Services\AI\AiPromptLibrary;

/**
 * 🛡️ SAB SEALED
 * Use case layer for listing generation wizard.
 */
class WizardAIService
{
    public function generate(array $input): void
    {
        // 🏗️ Build the request using production prompts
        $request = new AIRequest(
            purpose: 'listing_generation',
            model: config('services.deepseek.generation_model', 'deepseek-reasoner'),
            messages: [
                ['role' => 'user', 'content' => json_encode($input, JSON_UNESCAPED_UNICODE)]
            ],
            systemPrompt: AiPromptLibrary::WIZARD_GENERATION_PROMPT,
            maxTokens: 2048,
            temperature: 0.7,
            metadata: ['provider' => 'deepseek']
        );

        // ⚡ Dispatch to queue for async processing
        ProcessAiTaskJob::dispatch($request);
    }
}
