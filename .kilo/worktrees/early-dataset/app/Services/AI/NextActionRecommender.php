<?php

namespace App\Services\AI;

use App\Models\Lead;
use App\Models\AILeadScore;

class NextActionRecommender
{
    /**
     * Recommend the next best action for a lead.
     *
     * @param Lead $lead
     * @param AILeadScore|null $score
     * @return array [action, description, urgency]
     */
    public function recommend(Lead $lead, ?AILeadScore $score = null): array
    {
        // 1. Data Prep
        $currentScore = $score?->skor_degeri ?? 50;
        $winProb = $score?->win_probability ?? 0;
        $missingInfo = [];

        if (!$lead->budget_max) $missingInfo[] = 'Bütçe';
        if (!$lead->interested_location_id) $missingInfo[] = 'Lokasyon';

        // 2. Logic Matrix

        // CASE A: High Potential, Missing Info
        if ($winProb > 50 && count($missingInfo) > 0) {
            return [
                'action' => 'Bilgi Tamamla',
                'description' => 'Müşteri ilgili ancak ' . implode(' ve ', $missingInfo) . ' bilgisi eksik. Öğrenmek için iletişime geç.',
                'icon' => 'fa-clipboard-list',
                'color' => 'yellow',
                'urgency' => 'high'
            ];
        }

        // CASE B: Hot Lead (Win Prob > 70)
        if ($winProb >= 70) {
            return [
                'action' => 'Hemen Ara',
                'description' => 'Satış ihtimali çok yüksek. Zaman kaybetmeden telefonla görüş.',
                'icon' => 'fa-phone-volume',
                'color' => 'green',
                'urgency' => 'critical'
            ];
        }

        // CASE C: Warm Lead (Win Prob 40-69)
        if ($winProb >= 40) {
            return [
                'action' => 'WhatsApp Mesajı',
                'description' => 'İlgiyi canlı tutmak için portföy önerisi gönder.',
                'icon' => 'fa-whatsapp',
                'color' => 'blue',
                'urgency' => 'medium'
            ];
        }

        // CASE D: Cold Lead
        return [
            'action' => 'E-posta Kampanyası',
            'description' => 'Şu an öncelikli değil. Düzenli bülten listesine ekle.',
            'icon' => 'fa-envelope-open-text',
            'color' => 'gray',
            'urgency' => 'low'
        ];
    }
}
