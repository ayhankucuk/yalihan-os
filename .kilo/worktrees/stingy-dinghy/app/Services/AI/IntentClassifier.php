<?php

namespace App\Services\AI;

/**
 * Intent Classifier Service
 * 
 * Kullanıcı mesajının amacını (intent) belirler
 * 
 * Supported Intents:
 * - buy: Satın almak
 * - rent: Kiralamak
 * - price_check: Fiyat sorgusu
 * - info_request: Bilgi isteği
 * - appointment_request: Randevu talebi
 * - feedback: Geri bildirim
 * - inquiry: Genel soru
 * 
 * Örnek Kullanım:
 * $classifier = new IntentClassifier();
 * $intent = $classifier->classify("Bodrum'da satılık villa var mı?");
 * // Output: "buy"
 */
class IntentClassifier
{
    /**
     * Intent patterns - Her intent için keyword patterns
     */
    private array $patterns = [
        'buy' => [
            'keywords' => ['satın', 'al', 'almak', 'arıyorum', 'bul', 'bulabilir', 'var mı', 'alıcıyım'],
            'confidence' => 0.9,
            'negative_keywords' => ['kira', 'kiralık'],
        ],
        'rent' => [
            'keywords' => ['kirala', 'kiralık', 'kira', 'aylık', 'kiralamak', 'ihtiyacım'],
            'confidence' => 0.85,
            'negative_keywords' => ['satılık', 'satın'],
        ],
        'price_check' => [
            'keywords' => ['fiyat', 'kaç', 'ne kadar', 'ücret', 'maliyeti', 'parasal'],
            'confidence' => 0.8,
        ],
        'info_request' => [
            'keywords' => ['bilgi', 'detay', 'özellik', 'hakkında', 'nedir', 'özellikleri', 'tell me'],
            'confidence' => 0.75,
        ],
        'appointment_request' => [
            'keywords' => ['randevu', 'görüşmek', 'ziyaret', 'görmek', 'bakabilir', 'gezebilir'],
            'confidence' => 0.9,
        ],
        'feedback' => [
            'keywords' => ['beğendim', 'harika', 'müthiş', 'kötü', 'berbat', 'iyi', 'kötü', 'memnun'],
            'confidence' => 0.7,
        ],
    ];

    /**
     * Metinden intent belirle
     */
    public function classify(string $text): string
    {
        $normalized = mb_strtolower(trim($text), 'UTF-8');
        $scores = [];

        foreach ($this->patterns as $intent => $pattern) {
            $scores[$intent] = $this->scoreIntent($normalized, $pattern);
        }

        // En yüksek skoru al
        $topIntent = array_key_first($scores);
        $topScore = max($scores);

        // Confidence check (minimum 0.5)
        if ($topScore >= 0.5) {
            return $topIntent;
        }

        // Default intent
        return 'inquiry';
    }

    /**
     * Intent skoru hesapla
     */
    private function scoreIntent(string $text, array $pattern): float
    {
        $score = 0;
        $matchCount = 0;

        // Positive keywords match
        foreach ($pattern['keywords'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $matchCount++;
                $score += 0.3;
            }
        }

        // Negative keywords (penalty)
        if (isset($pattern['negative_keywords'])) {
            foreach ($pattern['negative_keywords'] as $negKeyword) {
                if (strpos($text, $negKeyword) !== false) {
                    $score -= 0.5;
                }
            }
        }

        // Match count multiplier (daha fazla keyword match = daha yüksek confidence)
        $score = min($score * (1 + $matchCount * 0.1), 1.0);

        return max($score, 0);
    }

    /**
     * Tüm intents için score döndür (debugging için)
     */
    public function classifyWithScores(string $text): array
    {
        $normalized = mb_strtolower(trim($text), 'UTF-8');
        $scores = [];

        foreach ($this->patterns as $intent => $pattern) {
            $scores[$intent] = $this->scoreIntent($normalized, $pattern);
        }

        arsort($scores); // Sort by score descending

        return $scores;
    }

    /**
     * Intent description (user-friendly)
     */
    public function getDescription(string $intent): string
    {
        $descriptions = [
            'buy' => 'Emlak satın almak',
            'rent' => 'Emlak kiralamak',
            'price_check' => 'Fiyat sorgulaması',
            'info_request' => 'Bilgi isteği',
            'appointment_request' => 'Randevu talebi',
            'feedback' => 'Geri bildirim',
            'inquiry' => 'Genel soru',
        ];

        return $descriptions[$intent] ?? 'Bilinmeyen intent';
    }
}
