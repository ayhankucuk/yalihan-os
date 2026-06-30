<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\App;

/**
 * 🎨 Opportunity Formatter Service
 *
 * Responsibility: Formats AI-generated opportunity analysis for the frontend in the current locale.
 */
class OpportunityFormatterService
{
    /**
     * Format the opportunity result for AI response.
     */
    public function formatExplanation(array $analysis): string
    {
        $locale = App::getLocale();
        $score = $analysis['score'] ?? 0;
        $reason = $analysis['reason'] ?? '';

        $messages = [
            'en' => "Opportunity Score: {$score}%. Reason: {$reason}. This listing shows high potential based on current market trends.",
            'tr' => "Fırsat Skoru: %{$score}. Neden: {$reason}. Bu ilan pazar verilerine göre yüksek yatırım potansiyeli taşımaktadır.",
            'ru' => "Оценка возможности: {$score}%. Причина: {$reason}. Это объявление показывает высокий потенциал на основе текущих рыночных тенденций.",
            'ar' => "درجة الفرصة: {$score}%. السبب: {$reason}. تُظهر هذه القائمة إمكانات عالية بناءً على اتجاهات السوق الحالية.",
            'de' => "Gelegenheit-Score: {$score}%. Grund: {$reason}. Dieses Angebot zeigt hohes Potenzial basierend auf aktuellen Markttrends.",
            'fr' => "Score d'opportunité: {$score}%. Raison: {$reason}. Cette annonce montre un potentiel élevé basé sur les tendances actuelles du marché.",
        ];

        return $messages[$locale] ?? $messages['en'];
    }
}
