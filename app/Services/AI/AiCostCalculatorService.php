<?php

namespace App\Services\AI;

class AiCostCalculatorService
{
    /**
     * AI Provider'a ve Modele göre tahmini maliyeti hesaplar.
     * USD cinsinden döner.
     */
    public function calculateCost(string $provider, ?string $model, int $inputTokens, int $outputTokens): float
    {
        if ($provider === 'ollama') {
            return 0.0;
        }

        if ($provider === 'deepseek') {
            // DeepSeek v4 API güncel fiyatları (varsayılan)
            // config('services.deepseek.pricing.input') vb. kullanılabilir.
            $inputCostPer1M = (float) config('services.deepseek.pricing.input', 0.14);
            $outputCostPer1M = (float) config('services.deepseek.pricing.output', 0.28);
            
            $inputCost = ($inputTokens / 1_000_000) * $inputCostPer1M;
            $outputCost = ($outputTokens / 1_000_000) * $outputCostPer1M;

            return $inputCost + $outputCost;
        }

        if ($provider === 'openai') {
            if ($model && strpos($model, 'gpt-4o') !== false) {
                // gpt-4o pricing
                return (($inputTokens / 1_000_000) * 5.00) + (($outputTokens / 1_000_000) * 15.00);
            }
            // fallback (gpt-3.5-turbo / gpt-4o-mini blended)
            return (($inputTokens / 1_000_000) * 0.15) + (($outputTokens / 1_000_000) * 0.60);
        }

        if ($provider === 'google') {
            // gemini-1.5-pro blended
            return (($inputTokens / 1_000_000) * 1.25) + (($outputTokens / 1_000_000) * 3.75);
        }

        return 0.0;
    }
}
