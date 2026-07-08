<?php

namespace App\Services\Media;

use App\Models\IlanFotografi;

/**
 * Room Detection Service — Sprint 6.3
 *
 * Kural tabanlı oda tespiti (V1 — AI Vision sonraki sprint).
 *
 * Algılama stratejisi:
 *   1. Dosya adı keyword analizi (en güvenilir)
 *   2. Dosya yolu pattern analizi
 *
 * AI Vision API entegrasyonu sonraki sprint'e bırakıldı.
 */
class RoomDetectionService
{
    /** @var array<string, array{label: string, keywords: string[], priority: int}> */
    private const ROOM_TYPES = [
        'pool' => [
            'label' => 'Havuz',
            'keywords' => ['havuz', 'pool', 'yuzme', 'yüzme', 'swimming'],
            'priority' => 10,
        ],
        'view' => [
            'label' => 'Manzara',
            'keywords' => ['manzara', 'view', 'deniz', 'sea', 'panorama', 'günbatımı', 'sunset', 'dogu', 'bati'],
            'priority' => 9,
        ],
        'living_room' => [
            'label' => 'Salon',
            'keywords' => ['salon', 'living', 'oturma', 'tv-odasi', 'tv_odasi', 'livingroom', 'saloon'],
            'priority' => 8,
        ],
        'bedroom' => [
            'label' => 'Yatak Odası',
            'keywords' => ['yatak', 'bedroom', 'yatik-odasi', 'yatik_odasi', 'bebek-odasi', 'spor-odasi'],
            'priority' => 7,
        ],
        'kitchen' => [
            'label' => 'Mutfak',
            'keywords' => ['mutfak', 'kitchen', 'mutfak-', 'beyaz-esya'],
            'priority' => 6,
        ],
        'bathroom' => [
            'label' => 'Banyo',
            'keywords' => ['banyo', 'bathroom', 'tuvalet', 'wc', 'dus', 'hamam'],
            'priority' => 5,
        ],
        'terrace' => [
            'label' => 'Teras',
            'keywords' => ['teras', 'terrace', 'balkon', 'balkonu'],
            'priority' => 4,
        ],
        'garden' => [
            'label' => 'Bahçe',
            'keywords' => ['bahce', 'bahçe', 'garden', 'yesil', 'yeşil', 'landscape'],
            'priority' => 3,
        ],
        'exterior' => [
            'label' => 'Dış Cephe',
            'keywords' => ['dis-cephe', 'dis_cephe', 'exterior', 'cephe', 'building', 'binanin', 'bina'],
            'priority' => 2,
        ],
    ];

    /**
     * Bir fotoğraf için oda türünü tespit et.
     *
     * @param  IlanFotografi  $fotograf
     * @return array{oda_turu: string, label: string, guven_skoru: int}
     */
    public function detect(IlanFotografi $fotograf): array
    {
        $searchText = $this->buildSearchText($fotograf);
        $searchLower = mb_strtolower($searchText);

        $bestMatch = null;
        $bestScore = 0;

        foreach (self::ROOM_TYPES as $key => $config) {
            $matches = 0;
            foreach ($config['keywords'] as $keyword) {
                if (mb_strpos($searchLower, mb_strtolower($keyword)) !== false) {
                    $matches++;
                }
            }

            if ($matches > 0) {
                // Skor = eşleşen keyword sayısı * öncelik
                $score = $matches * $config['priority'];

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = [
                        'oda_turu' => $key,
                        'label' => $config['label'],
                        'guven_skoru' => $this->calculateConfidence($matches, $config['priority']),
                    ];
                }
            }
        }

        if ($bestMatch === null) {
            return [
                'oda_turu' => 'other',
                'label' => 'Diğer',
                'guven_skoru' => 15,
            ];
        }

        return $bestMatch;
    }

    /**
     * Birden fazla fotoğraf için oda tespiti yap.
     *
     * @param  IlanFotografi[]  $fotograflar
     * @return array<int, array{oda_turu: string, label: string, guven_skoru: int}>  key = fotograf_id
     */
    public function detectBatch(array $fotograflar): array
    {
        $results = [];
        foreach ($fotograflar as $fotograf) {
            $results[$fotograf->id] = $this->detect($fotograf);
        }
        return $results;
    }

    /**
     * Tüm oda türlerini listele.
     *
     * @return array<string, string>  key = oda_turu, value = label
     */
    public function getAllRoomTypes(): array
    {
        return array_combine(
            array_keys(self::ROOM_TYPES),
            array_column(self::ROOM_TYPES, 'label'),
        );
    }

    /**
     * Arama metni oluştur (dosya adı + orijinal ad).
     */
    private function buildSearchText(IlanFotografi $fotograf): string
    {
        $parts = [];

        // Model kolonları: dosya_adi, dosya_yolu, aciklama, dosya_boyutu
        if ($fotograf->dosya_adi) {
            $parts[] = $fotograf->dosya_adi;
        }
        if ($fotograf->aciklama) {
            $parts[] = $fotograf->aciklama;
        }

        // Dosya yolundaki dizin isimlerini de ekle
        if ($fotograf->dosya_yolu) {
            $parts[] = dirname($fotograf->dosya_yolu);
            $parts[] = $fotograf->dosya_yolu;
        }

        return implode(' ', $parts);
    }

    /**
     * Güven skoru hesapla (0–100).
     */
    private function calculateConfidence(int $matchCount, int $priority): int
    {
        // Base: her keyword eşleşmesi +20 puan
        $score = min(95, $matchCount * 20);

        // Priority bonus: yüksek öncelikli odalar daha güvenilir
        $priorityBonus = ($priority / 10) * 5; // max +5

        return min(95, (int) round($score + $priorityBonus));
    }
}
