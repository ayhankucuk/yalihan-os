<?php

namespace App\Services\AI;

use Illuminate\Support\Str;
use App\Models\Ilan;
use App\Models\Talep;

/**
 * NLP (Natural Language Processing) Service
 *
 * Gelen mesajları analiz eder ve:
 * - Entity extraction (lokasyon, fiyat, tip, özellikler)
 * - Intent classification (satın al, kirala, soru sor, etc)
 * - Sentiment analysis (duygu analizi)
 * - Content analysis (anahtar kelimeleri çıkarma)
 *
 * Örnek Kullanım:
 * $nlp = new NLPProcessor();
 * $result = $nlp->parseMessage("Bodrum'da deniz manzaralı 3+1 daire arıyorum");
 */
class NLPProcessor
{
    /**
     * Türkçe emlak terminolojisi ve keywords
     */
    private array $keywords = [
        // Lokasyonlar (Expanded)
        'locations' => [
            'bodrum' => 'Bodrum',
            'yalıkavak' => 'Yalıkavak',
            'turgutreis' => 'Turgutreis',
            'ortakent' => 'Ortakent',
            'merkez' => 'Merkez',
            'sahil' => 'Sahil',
            'plaj' => 'Plaj',
            'bitez' => 'Bitez',
            'gündoğan' => 'Gündoğan',
            'gölköy' => 'Gölköy',
            'türkbükü' => 'Türkbükü',
            'yahşi' => 'Yahşi',
            'akyarlar' => 'Akyarlar',
            'gumusluk' => 'Gümüşlük',
            'gümüşlük' => 'Gümüşlük',
        ],

        // Emlak Tipleri (Expanded)
        'property_types' => [
            'bağ' => 'vineyard',
            'arsa' => 'land',
            'apartment' => 'apartment',
            'flat' => 'apartment',
            'house' => 'house',
            'land' => 'land',
            'plot' => 'land',
            'commercial' => 'commercial',
            'office' => 'office',
            'shop' => 'shop',
        ],

        // İşlem Tipi
        'transaction_types' => [
            'satılık' => 'sale',
            'kiralık' => 'rent',
            'devren' => 'transfer',
            'for sale' => 'sale',
            'for rent' => 'rent',
            'buy' => 'purchase',
            'residential' => 'residential',
        ],

        // Özellikler (Expanded)
        'features' => [
            'havuz' => 'pool',
            'bahçe' => 'garden',
            'balkon' => 'balcony',
            'teras' => 'terrace',
            'asansör' => 'elevator',
            'otopark' => 'parking',
            'sauna' => 'sauna',
            'deniz' => 'sea_view',
            'manzara' => 'view',
            'yeni' => 'new',
            'modern' => 'modern',
            'lüks' => 'luxury',
            'eşyalı' => 'furnished',
            'klima' => 'ac',
            'şömine' => 'fireplace',
            'jakuzi' => 'jacuzzi',
            'güvenlik' => 'security',
        ],

        // Oda Sayıları
        'rooms' => [
            '1+1' => '1+1',
            '2+1' => '2+1',
            '3+1' => '3+1',
            '4+1' => '4+1',
            '5+1' => '5+1',
            '6+1' => '6+1',
            'stüdyo' => '1+0',
            'tek oda' => '1+0',
        ],
    ];

    /**
     * Common Turkish Typos and Abbreviations
     */
    private array $typo_map = [
        'bodrumda' => 'bodrum',
        'yalikavak' => 'yalıkavak',
        'gumusluk' => 'gümüşlük',
        'm2' => 'metrekare',
        'mt2' => 'metrekare',
        'fıyat' => 'fiyat',
        'satilik' => 'satılık',
        'kiralik' => 'kiralık',
    ];

    /**
     * Gelen metni parse et ve tüm bilgileri çıkar
     *
     * @param string $message Kullanıcı mesajı
     * @return array Parsed data (entities, intent, sentiment)
     */
    public function parseMessage(string $message): array
    {
        $normalized = $this->normalize($message);

        return [
            'original' => $message,
            'normalized' => $normalized,
            'entities' => $this->extractEntities($normalized),
            'intent' => $this->classifyIntent($normalized),
            'sentiment' => $this->analyzeSentiment($normalized),
            'keywords' => $this->extractKeywords($normalized),
            'confidence' => $this->calculateConfidence($normalized),
        ];
    }

    /**
     * Metni normalize et (lowercase, trim, typo handling)
     */
    private function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        // Typo handling
        foreach ($this->typo_map as $typo => $correct) {
            $text = str_replace($typo, $correct, $text);
        }

        $text = trim($text);
        $text = preg_replace('/\s+/', ' ', $text); // Multiple spaces → single space
        return $text;
    }

    /**
     * Entity extraction - Lokasyon, fiyat, tip, özellikler çıkar
     */
    public function extractEntities(string $text): array
    {
        $entities = [
            'locations' => $this->extractLocations($text),
            'price' => $this->extractPrice($text),
            'property_type' => $this->extractPropertyType($text),
            'transaction_type' => $this->extractTransactionType($text),
            'rooms' => $this->extractRooms($text),
            'features' => $this->extractFeatures($text),
            'area' => $this->extractArea($text),
            'timeframe' => $this->extractTimeframe($text),
        ];

        return array_filter($entities, fn($v) => !is_null($v) && (!is_array($v) || !empty($v)));
    }

    /**
     * Zaman dilimi çıkar
     * Pattern: "hemen", "haftaya", "yaza kadar", "acil"
     */
    private function extractTimeframe(string $text): ?string
    {
        if ($this->matchesPattern($text, ['acil', 'hemen', 'şimdi', 'asap'])) {
            return 'immediate';
        }
        if ($this->matchesPattern($text, ['yaz', 'sezon', 'haziran', 'temmuz'])) {
            return 'summer_season';
        }
        if ($this->matchesPattern($text, ['uzun dönem', '1 yıl', 'yıllık'])) {
            return 'long_term';
        }
        return null;
    }

    /**
     * Lokasyonları çıkar (Birden fazla destekler)
     */
    private function extractLocations(string $text): array
    {
        $locations = [];
        foreach ($this->keywords['locations'] as $keyword => $canonical) {
            if (strpos($text, $keyword) !== false) {
                if (!in_array($canonical, $locations)) {
                    $locations[] = $canonical;
                }
            }
        }
        return $locations;
    }

    /**
     * Fiyat çıkar
     * Pattern: "2-3 milyon", "500 bin", "1.5 milyon", "2000000"
     */
    private function extractPrice(string $text): ?array
    {
        $price_min = null;
        $price_max = null;
        $currency = 'TRY';

        // Pattern: "2-3 milyon TL"
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*-\s*(\d+(?:[.,]\d+)?)\s*(milyon|bin|usd|dolar)/i', $text, $matches)) {
            $price_min = $this->parsePrice($matches[1], $matches[3]);
            $price_max = $this->parsePrice($matches[2], $matches[3]);
            $currency = strpos($matches[3], 'usd') !== false || strpos($matches[3], 'dolar') !== false ? 'USD' : 'TRY';
        }
        // Pattern: "2 milyon TL"
        elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*(milyon|bin|usd|dolar)/i', $text, $matches)) {
            $price_min = $this->parsePrice($matches[1], $matches[2]);
            $price_max = $price_min;
            $currency = strpos($matches[2], 'usd') !== false || strpos($matches[2], 'dolar') !== false ? 'USD' : 'TRY';
        }

        if ($price_min || $price_max) {
            return [
                'min' => $price_min,
                'max' => $price_max,
                'currency' => $currency,
            ];
        }

        return null;
    }

    /**
     * Fiyat stringi → integer dönüştür
     * "2" + "milyon" → 2000000
     * "500" + "bin" → 500000
     */
    private function parsePrice(string $amount, string $unit): int
    {
        $amount = (float) str_replace(',', '.', $amount);
        $multipliers = [
            'milyon' => 1_000_000,
            'million' => 1_000_000,
            'bin' => 1_000,
            'thousand' => 1_000,
            'k' => 1_000,
        ];

        $unit = mb_strtolower($unit, 'UTF-8');
        $multiplier = $multipliers[$unit] ?? 1;

        return (int) ($amount * $multiplier);
    }

    /**
     * Emlak tipi çıkar (daire, villa, arsa, etc)
     */
    private function extractPropertyType(string $text): ?string
    {
        foreach ($this->keywords['property_types'] as $keyword => $canonical) {
            if (strpos($text, $keyword) !== false) {
                return $canonical;
            }
        }
        return null;
    }

    /**
     * İşlem tipi çıkar (satılık, kiralık, etc)
     */
    private function extractTransactionType(string $text): ?string
    {
        foreach ($this->keywords['transaction_types'] as $keyword => $canonical) {
            if (strpos($text, $keyword) !== false) {
                return $canonical;
            }
        }
        return null;
    }

    /**
     * Oda sayısı çıkar (1+1, 2+1, 3+1, etc)
     */
    private function extractRooms(string $text): ?string
    {
        foreach ($this->keywords['rooms'] as $keyword => $canonical) {
            if (strpos($text, $keyword) !== false) {
                return $keyword;
            }
        }
        return null;
    }

    /**
     * Özellikler çıkar (havuz, bahçe, deniz manzarası, etc)
     */
    private function extractFeatures(string $text): array
    {
        $features = [];
        foreach ($this->keywords['features'] as $keyword => $canonical) {
            if (strpos($text, $keyword) !== false) {
                $features[] = $canonical;
            }
        }
        return $features;
    }

    /**
     * Alan (metrekare) çıkar
     * Pattern: "100 m2", "100m2", "100 metrekare"
     */
    private function extractArea(string $text): ?array
    {
        if (preg_match('/(\d+)\s*(?:m2|m²|metrekare)/i', $text, $matches)) {
            return [
                'value' => (int) $matches[1],
                'unit' => 'm2',
            ];
        }
        return null;
    }

    /**
     * Intent classification - Kullanıcının amacını belirle
     *
     * Intents:
     * - buy: Satın almak istiyor
     * - rent: Kiralamak istiyor
     * - inquire: Bilgi almak istiyor
     * - price_check: Fiyat sorgusu
     * - info_request: Bilgi isteği
     * - feedback: Geri bildirim
     */
    public function classifyIntent(string $text): string
    {
        // Kiralama intent'i (Öncelikli)
        if ($this->matchesPattern($text, ['kirala', 'kiralık', 'kira', 'aylık'])) {
            return 'rent';
        }

        // Satın alma intent'i
        if ($this->matchesPattern($text, ['satın', 'al', 'almak', 'arıyorum', 'bul', 'bulabilir', 'satılık'])) {
            return 'buy';
        }

        // Yatırım intent'i
        if ($this->matchesPattern($text, ['yatırım', 'kelepir', 'fırsat', 'amortisman', 'getiri'])) {
            return 'investment';
        }

        // Fiyat sorgusu
        if ($this->matchesPattern($text, ['fiyat', 'kaç', 'ne kadar', 'ücret'])) {
            return 'price_check';
        }

        // Bilgi isteği
        if ($this->matchesPattern($text, ['bilgi', 'detay', 'özellik', 'hakkında', 'nedir'])) {
            return 'info_request';
        }

        // Randevu talebi
        if ($this->matchesPattern($text, ['randevu', 'görüşmek', 'ziyaret', 'görmek', 'bakabilir'])) {
            return 'appointment_request';
        }

        // Geri bildirim
        if ($this->matchesPattern($text, ['beğendim', 'harika', 'müthiş', 'kötü', 'berbat'])) {
            return 'feedback';
        }

        // Default: Inquiry (genel soru)
        return 'inquiry';
    }

    /**
     * Pattern matching helper
     */
    private function matchesPattern(string $text, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (strpos($text, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Duygu analizi - Müşteri duygularını belirle
     *
     * Sentiments:
     * - positive: "Harika", "Çok iyi"
     * - negative: "Çok pahalı", "Berbat"
     * - neutral: Nötr
     * - urgent: "Acil", "Hemen"
     */
    public function analyzeSentiment(string $text): string
    {
        // Pozitif duygu
        if ($this->matchesPattern($text, ['harika', 'müthiş', 'süper', 'beğendim', 'mükemmel', 'çok iyi'])) {
            return 'positive';
        }

        // Negatif duygu
        if ($this->matchesPattern($text, ['berbat', 'kötü', 'pahalı', 'ucuz', 'memnun değil', 'hoşlanmadım'])) {
            return 'negative';
        }

        // Acil durum
        if ($this->matchesPattern($text, ['acil', 'hemen', 'çabuk', 'urgently', 'immediately'])) {
            return 'urgent';
        }

        // Nötr (default)
        return 'neutral';
    }

    /**
     * Anahtar kelimeleri çıkar
     */
    public function extractKeywords(string $text): array
    {
        // Basit keyword extraction (kelimeleri ayır, stopwords hariç)
        $stopwords = ['ve', 'bu', 'şu', 'var', 'yok', 'mi', 'mi', 'de', 'da'];
        $words = explode(' ', $text);

        return array_filter($words, function($word) use ($stopwords) {
            return strlen($word) > 2 && !in_array($word, $stopwords);
        });
    }

    /**
     * Parsing confidence score (0-1)
     * Ne kadar entity bulunursa confidence artar
     */
    public function calculateConfidence(string $text): float
    {
        $score = 0;
        $maxScore = 6; // Max 6 entity type

        // Check entity count
        if (preg_match('/bodrum|yalıkavak|turgutreis/i', $text)) $score++;
        if (preg_match('/milyon|bin|usd/i', $text)) $score++;
        if (preg_match('/daire|villa|arsa/i', $text)) $score++;
        if (preg_match('/1\+1|2\+1|3\+1/i', $text)) $score++;
        if (preg_match('/havuz|bahçe|deniz/i', $text)) $score++;
        if (strlen($text) > 30) $score++; // Longer messages = more detailed

        return min($score / $maxScore, 1.0);
    }

    /**
     * Response generate - Yanıt oluştur
     */
    public function generateResponse(array $parsed): string
    {
        $intent = $parsed['intent'] ?? 'inquiry';
        $locations = $parsed['entities']['locations'] ?? [];
        $location_text = !empty($locations) ? implode(' ve ', $locations) : null;
        $property_type = $parsed['entities']['property_type'] ?? null;

        // Intent'e göre cevap
        switch ($intent) {
            case 'buy':
                if ($location_text && $property_type) {
                    return "{$location_text}'da {$property_type} arıyorsunuz. Şu an için uygun ilanları arıyorum...";
                }
                return "Satın almak istediğiniz emlakın özelliklerini biraz daha detaylandırabilir misiniz?";

            case 'rent':
                if ($location_text) {
                    return "{$location_text}'da kiralanabilecek ilanlar için tarama yapıyorum...";
                }
                return "Kiralamak istediğiniz bölgeyi ve emlak türünü belirtebilir misiniz?";

            case 'price_check':
                if ($property_type) {
                    return "{$property_type} fiyatları hakkında bilgi verebilirim. Nerede ve ne zaman yaşamak istersiniz?";
                }
                return "Emlak fiyatları konusunda yardımcı olabilirim.";

            case 'appointment_request':
                return "Randevu talebiniz alınmıştır. Hemen danışmanımız tarafından sizinle iletişime geçilecektir.";

            default:
                return "Size nasıl yardımcı olabilirim? Bir emlak arıyor musunuz yoksa bilgi mi istiyorsunuz?";
        }
    }
}
