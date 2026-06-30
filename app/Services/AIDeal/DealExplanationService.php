<?php

namespace App\Services\AIDeal;

/**
 * ️ SAB SEALED
 * Deal Explanation Service
 * Generates human-readable, multi-language justifications for deal predictions.
 */
class DealExplanationService
{
    /**
     * Generate explanation based on scores and locale.
     */
    public function explain(array $scores, string $locale = 'tr'): string
    {
        $translations = [
            'tr' => [
                'high_prob' => 'Bu ilan, piyasa fiyatına uygunluğu ve yüksek lokasyon talebi nedeniyle hızlı satış potansiyeline sahip.',
                'med_prob' => 'İlan dengeli bir konumda, ancak etkileşim artırılırsa satış hızı yükselebilir.',
                'low_prob' => 'Fiyat/market uyumu düşük görünüyor, fiyat revizyonu veya pazarlama çalışması önerilir.',
                'hot_market' => 'Bölgedeki pazar hareketliliği satış şansını %:boost artırıyor.',
                'velocity_note' => 'İlanın gördüğü ilgi (:favorites favori, :views izlenme) alıcı iştahını doğruluyor.',
            ],
            'en' => [
                'high_prob' => 'This listing has high sales potential due to market price alignment and strong location demand.',
                'med_prob' => 'Balanced position, increasing engagement could boost sales velocity.',
                'low_prob' => 'Price/market alignment appears low, a price revision or marketing push is recommended.',
                'hot_market' => 'Market activity in the area increases the sales chance by :boost%.',
                'velocity_note' => 'Recent engagement (:favorites favorites, :views views) confirms buyer appetite.',
            ],
            // RU, AR, DE, FR would be added here similarly
        ];

        $set = $translations[$locale] ?? $translations['tr'];

        $prob = $scores['sale_probability'];
        $heat = $scores['market_heat_score'];

        $explanation = '';

        if ($prob >= 75) {
            $explanation = $set['high_prob'];
        } elseif ($prob >= 50) {
            $explanation = $set['med_prob'];
        } else {
            $explanation = $set['low_prob'];
        }

        if ($heat > 80) {
            $boost = $heat - 50;
            $explanation .= ' ' . str_replace(':boost', $boost, $set['hot_market']);
        }

        return $explanation;
    }
}
