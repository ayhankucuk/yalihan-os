<?php

namespace App\Services;

use App\Services\Cache\CacheHelper;

class AICoreSystem
{
    private $storageManager;

    private $promptManager;

    private $learningEngine;

    // public function __construct(AILearningEngine $learningEngine)
    public function __construct()
    {
        $this->storageManager = new FlexibleStorageManager;
        $this->promptManager = new AIPromptManager;
        // $this->learningEngine = $learningEngine;
    }

    /**
     * AI'yi öğret
     */
    public function teachAI($context, $task, $expectedOutput)
    {
        $prompt = $this->promptManager->generatePrompt($context, $task);
        $result = []; // $this->learningEngine->process($prompt, $expectedOutput);

        // Öğrenilen pattern'i kaydet
        $this->storageManager->store("pattern_{$context}", [
            'prompt' => $prompt,
            'result' => $result,
            'context' => $context,
            'learned_at' => now(),
        ]);

        return $result;
    }

    /**
     * AI'den öğren
     */
    public function learnFromAI($context, $input)
    {
        $patterns = $this->storageManager->get("pattern_{$context}");

        return []; // $this->learningEngine->applyPatterns($patterns, $input);
    }

    /**
     * AI'yi test et
     */
    public function testAI($context, $input)
    {
        $learnedPatterns = $this->getLearnedPatterns($context);

        if ($learnedPatterns) {
            return []; // $this->learningEngine->applyPatterns($learnedPatterns, $input);
        }

        return $this->generateDefaultResponse($context, $input);
    }

    /**
     * Öğrenilen pattern'leri getir
     */
    private function getLearnedPatterns($context)
    {
        // ✅ STANDARDIZED: Using CacheHelper with context param
        return CacheHelper::remember(
            'ai',
            'patterns',
            'medium',
            function () use ($context) {
                return \App\Models\AICoreSystem::where('context', $context)
                    ->where('aktiflik_durumu', 1) // Context7: aktiflik_durumu (boolean)
                    ->orderBy('success_rate', 'desc') // context7-ignore
                    ->first();
            },
            ['context' => $context]
        );
    }

    /**
     * Varsayılan yanıt oluştur
     */
    private function generateDefaultResponse($context, $input)
    {
        $aiService = app(AIService::class);

        // Input'u string'e çevir
        if (is_array($input)) {
            $input = json_encode($input);
        }

        $prompt = "Context: {$context}\nInput: {$input}\n\nProvide a helpful response:";

        return $aiService->generate($prompt, [
            'model' => 'ollama',
            'temperature' => 0.7,
            'max_tokens' => 500,
        ]);
    }

    /**
     * AI başarı oranını güncelle
     */
    public function updateSuccessRate($context, $taskType, $isSuccess)
    {
        $current = \App\Models\AICoreSystem::where('context', $context)
            ->where('task_type', $taskType)
            ->first();

        if ($current) {
            $newCount = $current->usage_count + 1;
            $newSuccess = $current->success_rate + ($isSuccess ? 1 : 0);
            $newRate = ($newSuccess / $newCount) * 100;

            $current->update([
                'success_rate' => $newRate,
                'usage_count' => $newCount,
            ]);
        }
    }
}
