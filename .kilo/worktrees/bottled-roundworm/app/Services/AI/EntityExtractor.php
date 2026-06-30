<?php

namespace App\Services\AI;

/**
 * Entity Extractor Service
 * 
 * Gelen metinden structured data (entities) çıkarır:
 * - Lokasyon (Bodrum, Yalıkavak)
 * - Fiyat (2-3 milyon TL)
 * - Emlak Tipi (daire, villa, arsa)
 * - İşlem Tipi (satılık, kiralık)
 * - Oda Sayısı (1+1, 2+1, 3+1)
 * - Özellikler (havuz, bahçe, deniz manzarası)
 * - Alan (metrekare)
 * 
 * Örnek Kullanım:
 * $extractor = new EntityExtractor();
 * $entities = $extractor->extract("Bodrum'da 2-3 milyon TL'ye deniz manzaralı 3+1 daire");
 * // Output: {
 * //   'location': 'Bodrum',
 * //   'price': {'min': 2000000, 'max': 3000000},
 * //   'property_type': 'apartment',
 * //   'rooms': '3+1',
 * //   'features': ['sea_view']
 * // }
 */
class EntityExtractor
{
    /**
     * Türkçe emlak veri sözlüğü
     */
    private array $entities = [
        'locations' => [
            'bodrum' => ['id' => 1, 'name' => 'Bodrum', 'province' => 'Muğla'],
            'yalıkavak' => ['id' => 2, 'name' => 'Yalıkavak', 'province' => 'Muğla'],
            'turgutreis' => ['id' => 3, 'name' => 'Turgutreis', 'province' => 'Muğla'],
            'ortakent' => ['id' => 4, 'name' => 'Ortakent', 'province' => 'Muğla'],
            'merkez' => ['id' => 5, 'name' => 'Merkez', 'province' => 'Various'],
            'sahil' => ['id' => 6, 'name' => 'Sahil', 'province' => 'Various'],
        ],

        'property_types' => [
            'daire' => ['id' => 1, 'name' => 'Daire', 'code' => 'apartment'],
            'villa' => ['id' => 2, 'name' => 'Villa', 'code' => 'villa'],
            'arsa' => ['id' => 3, 'name' => 'Arsa', 'code' => 'land'],
            'ev' => ['id' => 4, 'name' => 'Ev', 'code' => 'house'],
            'işyeri' => ['id' => 5, 'name' => 'İşyeri', 'code' => 'commercial'],
            'ofis' => ['id' => 6, 'name' => 'Ofis', 'code' => 'office'],
        ],

        'transaction_types' => [
            'satılık' => 'sale',
            'kiralık' => 'rent',
            'kirala' => 'rent',
            'sat' => 'sale',
            'al' => 'purchase',
            'satın' => 'purchase',
        ],

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
            'klimatik' => 'ac',
            'isıtma' => 'heating',
        ],

        'rooms' => [
            '1+1' => 'one_plus_one',
            '2+1' => 'two_plus_one',
            '3+1' => 'three_plus_one',
            '4+1' => 'four_plus_one',
            '5+1' => 'five_plus_one',
        ],
    ];

    /**
     * Metinden tüm entities çıkar
     */
    public function extract(string $text): array
    {
        $normalized = mb_strtolower(trim($text), 'UTF-8');

        return [
            'location' => $this->extractLocation($normalized),
            'price' => $this->extractPrice($normalized),
            'property_type' => $this->extractPropertyType($normalized),
            'transaction_type' => $this->extractTransactionType($normalized),
            'rooms' => $this->extractRooms($normalized),
            'features' => $this->extractFeatures($normalized),
            'area' => $this->extractArea($normalized),
            'age' => $this->extractAge($normalized),
        ];
    }

    /**
     * Lokasyon çıkar
     */
    public function extractLocation(string $text): ?array
    {
        foreach ($this->entities['locations'] as $keyword => $location) {
            if (strpos($text, $keyword) !== false) {
                return [
                    'keyword' => $keyword,
                    'data' => $location,
                    'confidence' => 0.95,
                ];
            }
        }
        return null;
    }

    /**
     * Fiyat çıkar
     */
    public function extractPrice(string $text): ?array
    {
        // Pattern: "2-3 milyon TL"
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*[-–]\s*(\d+(?:[.,]\d+)?)\s*(milyon|bin|usd|dolar|₺|tl)/i', $text, $matches)) {
            return [
                'min' => $this->parsePrice($matches[1], $matches[3]),
                'max' => $this->parsePrice($matches[2], $matches[3]),
                'currency' => $this->resolveCurrency($matches[3]),
                'confidence' => 0.98,
            ];
        }

        // Pattern: "2 milyon TL"
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(milyon|bin|usd|dolar|₺|tl)/i', $text, $matches)) {
            $price = $this->parsePrice($matches[1], $matches[2]);
            return [
                'min' => $price,
                'max' => $price,
                'currency' => $this->resolveCurrency($matches[2]),
                'confidence' => 0.95,
            ];
        }

        return null;
    }

    /**
     * Fiyat parse
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
     * Para birimi belirle
     */
    private function resolveCurrency(string $unit): string
    {
        $unit = mb_strtolower($unit, 'UTF-8');
        if (strpos($unit, 'usd') !== false || strpos($unit, 'dolar') !== false) {
            return 'USD';
        }
        return 'TRY';
    }

    /**
     * Emlak tipi çıkar
     */
    public function extractPropertyType(string $text): ?array
    {
        foreach ($this->entities['property_types'] as $keyword => $type) {
            if (strpos($text, $keyword) !== false) {
                return [
                    'keyword' => $keyword,
                    'data' => $type,
                    'confidence' => 0.95,
                ];
            }
        }
        return null;
    }

    /**
     * İşlem tipi çıkar
     */
    public function extractTransactionType(string $text): ?array
    {
        foreach ($this->entities['transaction_types'] as $keyword => $type) {
            if (strpos($text, $keyword) !== false) {
                return [
                    'keyword' => $keyword,
                    'type' => $type, // context7-ignore
                    'confidence' => 0.9,
                ];
            }
        }
        return null;
    }

    /**
     * Oda sayısı çıkar
     */
    public function extractRooms(string $text): ?array
    {
        foreach ($this->entities['rooms'] as $keyword => $code) {
            if (strpos($text, $keyword) !== false) {
                return [
                    'keyword' => $keyword,
                    'code' => $code,
                    'confidence' => 0.98,
                ];
            }
        }
        return null;
    }

    /**
     * Özellikler çıkar
     */
    public function extractFeatures(string $text): array
    {
        $features = [];

        foreach ($this->entities['features'] as $keyword => $code) {
            if (strpos($text, $keyword) !== false) {
                $features[] = [
                    'keyword' => $keyword,
                    'code' => $code,
                    'confidence' => 0.85,
                ];
            }
        }

        return $features;
    }

    /**
     * Alan (metrekare) çıkar
     */
    public function extractArea(string $text): ?array
    {
        if (preg_match('/(\d+)\s*(?:m2|m²|metrekare|m\.kare)/i', $text, $matches)) {
            return [
                'value' => (int) $matches[1],
                'unit' => 'm2',
                'confidence' => 0.95,
            ];
        }
        return null;
    }

    /**
     * Yapı yaşı çıkar
     */
    public function extractAge(string $text): ?array
    {
        // "2 yıllık" pattern
        if (preg_match('/(\d+)\s*(?:yıl|yaş)/i', $text, $matches)) {
            return [
                'age' => (int) $matches[1],
                'unit' => 'year',
                'confidence' => 0.85,
            ];
        }

        // "yeni" pattern
        if (strpos($text, 'yeni') !== false) {
            return [
                'age' => 0,
                'text' => 'yeni',
                'confidence' => 0.8,
            ];
        }

        return null;
    }

    /**
     * Tüm entities'lerin confidence score'u
     */
    public function calculateEntityConfidence(array $entities): float
    {
        $totalConfidence = 0;
        $count = 0;

        foreach ($entities as $entity) {
            if (is_array($entity) && isset($entity['confidence'])) {
                $totalConfidence += $entity['confidence'];
                $count++;
            }
        }

        return $count > 0 ? $totalConfidence / $count : 0;
    }
}
