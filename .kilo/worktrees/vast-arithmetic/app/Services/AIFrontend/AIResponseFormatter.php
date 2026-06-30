<?php

namespace App\Services\AIFrontend;

use App\Services\AIService;
use Illuminate\Support\Facades\App;

class AIResponseFormatter
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Format the final response for the frontend assistant.
     * Combines raw data with LLM-generated explanation.
     */
    public function format(array $data, string $originalQuery): array
    {
        $isBuyerMatch = $data['intent']['is_buyer_match'] ?? false;
        $isDealPrediction = $data['intent']['is_deal_prediction'] ?? false;
        $recommendations = $data['analysis']['top_recommendations'] ?? [];
        $prediction = $data['analysis']['prediction'] ?? null;
        $locale = App::getLocale();

        if ($locale === 'en') {
            $context = $isBuyerMatch ? "potential buyers for this listing" : "listing results";
            if ($isDealPrediction && $prediction) {
                $context = "deal prediction analysis";
                $dataStr = json_encode($prediction['scores']);
            } else {
                $dataStr = json_encode($recommendations);
            }

            $explanationPrompt = "You are a real estate advisor. To the user's question: '{$originalQuery}'\n"
                . "I found these {$context}: " . $dataStr . "\n"
                . "Write a friendly, professional explanation in English.";

            if (!$isDealPrediction) $explanationPrompt .= " Highlight the top 3 options.";
            if ($isDealPrediction && $prediction) $explanationPrompt .= " Explain the sale probability and price accuracy.";

        } else {
            $context = $isBuyerMatch ? "bu ilan için potansiyel alıcıları" : "ilan sonuçlarını";
            if ($isDealPrediction && $prediction) {
                $context = "satış tahmini analizini";
                $dataStr = json_encode($prediction['scores']);
            } else {
                $dataStr = json_encode($recommendations);
            }

            $explanationPrompt = "Sen bir emlak danışmanısın. Kullanıcının şu sorusuna: '{$originalQuery}'\n"
                . "Şu {$context} buldum: " . $dataStr . "\n"
                . "Kullanıcıya samimi, profesyonel bir açıklama yaz.";

            if (!$isDealPrediction) $explanationPrompt .= " En iyi 3 seçeneği vurgula.";
            if ($isDealPrediction && $prediction) $explanationPrompt .= " Satış olasılığını ve fiyat doğruluğunu açıkla.";
        }

        $explanation = $this->aiService->generate($explanationPrompt, [
            'temperature' => 0.7,
            'max_tokens' => 500
        ]);

        return [
            'intent' => $data['intent'],
            'results' => $data['results'],
            'analysis' => $data['analysis'],
            'explanation' => $explanation,
        ];
    }
}
