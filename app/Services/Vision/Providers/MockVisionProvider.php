<?php

namespace App\Services\Vision\Providers;

use App\DTOs\Vision\VisionAnalysisDTO;
use App\DTOs\Vision\VisionObjectDTO;
use App\Services\Vision\Contracts\VisionProviderContract;

/**
 * Mock Vision Provider — Sprint 6.4
 *
 * Test ve development ortamı için simulated AI Vision.
 * Gerçek API çağrısı yapmaz.
 */
class MockVisionProvider implements VisionProviderContract
{
    private const MOCK_ROOMS = [
        'living_room'   => ['label' => 'Salon', 'confidence' => 0.92],
        'kitchen'       => ['label' => 'Mutfak', 'confidence' => 0.89],
        'bedroom'       => ['label' => 'Yatak Odası', 'confidence' => 0.94],
        'bathroom'      => ['label' => 'Banyo', 'confidence' => 0.87],
        'dining_room'   => ['label' => 'Yemek Odası', 'confidence' => 0.85],
        'pool'          => ['label' => 'Havuz', 'confidence' => 0.96],
        'garden'        => ['label' => 'Bahçe', 'confidence' => 0.90],
        'terrace'       => ['label' => 'Teras', 'confidence' => 0.88],
        'exterior'      => ['label' => 'Dış Cephe', 'confidence' => 0.91],
        'view'          => ['label' => 'Manzara', 'confidence' => 0.95],
        'other'         => ['label' => 'Diğer', 'confidence' => 0.70],
    ];

    private const MOCK_AMENITIES = [
        ['slug' => 'klima', 'label' => 'Klima', 'confidence' => 0.95],
        ['slug' => 'Wifi', 'label' => 'WiFi', 'confidence' => 0.90],
        ['slug' => 'otopark', 'label' => 'Otopark', 'confidence' => 0.88],
        ['slug' => 'guvenlik', 'label' => 'Güvenlik Sistemi', 'confidence' => 0.92],
        ['slug' => 'akilli-ev', 'label' => 'Akıllı Ev Sistemi', 'confidence' => 0.85],
        ['slug' => 'spotsistemi', 'label' => 'Spot Aydınlatma', 'confidence' => 0.80],
    ];

    private const MOCK_LUXURY = [
        ['slug' => 'marble', 'label' => 'Mermer Yüzeyler', 'confidence' => 0.93],
        ['slug' => 'aski-menaras', 'label' => 'Asma Kat / Teras', 'confidence' => 0.87],
        ['slug' => 'cam cephane', 'label' => 'Cam Cephe', 'confidence' => 0.89],
        ['slug' => 'ozel-havuz', 'label' => 'Özel Havuz', 'confidence' => 0.96],
        ['slug' => 'hamam', 'label' => 'Türk Hamamı', 'confidence' => 0.84],
    ];

    private const MOCK_FURNITURE = [
        ['slug' => 'lüks-kanepe', 'label' => 'Lüks Kanepe', 'confidence' => 0.88],
        ['slug' => 'modern-masa', 'label' => 'Modern Yemek Masası', 'confidence' => 0.86],
        ['slug' => 'yürüyüş-rakami', 'label' => 'Yürüyüş Ranzası', 'confidence' => 0.82],
        ['slug' => 'ankastre-cihazlar', 'label' => 'Ankastre Cihazlar', 'confidence' => 0.91],
    ];

    public function analyze(string $imagePath, array $context = []): VisionAnalysisDTO
    {
        $roomHint  = $context['room_hint'] ?? 'other';
        $ilanId    = $context['ilan_id'] ?? 0;
        $fotoId    = $context['fotograf_id'] ?? 0;

        $roomData  = self::MOCK_ROOMS[$roomHint] ?? self::MOCK_ROOMS['other'];
        $roomType  = $roomHint;
        $roomLabel = $roomData['label'];

        // File name analysis for enhanced mock results
        $fileName   = basename($imagePath);
        $fileNameLower = strtolower($fileName);

        $rooms   = [new VisionObjectDTO(
            type: 'oda',
            label: $roomLabel,
            confidence: (float) $roomData['confidence'],
            provider: 'mock',
            reason: "Mock: Dosya adında '{$roomHint}' analiz edildi.",
            metadata: ['source' => 'filename_analysis'],
        )];

        $amenities = $this->extractByKeywords(self::MOCK_AMENITIES, $fileNameLower);
        $luxury    = $this->extractByKeywords(self::MOCK_LUXURY, $fileNameLower);
        $furniture = $this->extractByKeywords(self::MOCK_FURNITURE, $fileNameLower);

        $allObjects = array_merge($rooms, $amenities, $luxury, $furniture);
        $avgConfidence = empty($allObjects)
            ? 0.0
            : round(array_sum(array_column(array_map(fn($o) => $o->toArray(), $allObjects), 'confidence')) / count($allObjects), 3);

        $qualityScore = $this->calcMockQualityScore($fileNameLower);

        return new VisionAnalysisDTO(
            fotograf_id: $fotoId,
            objects: [],
            rooms: $rooms,
            furniture: $furniture,
            amenities: $amenities,
            luxuryFeatures: $luxury,
            views: $this->detectViews($fileNameLower),
            architecturalStyles: $this->detectStyle($fileNameLower),
            ai_quality_score: $qualityScore,
            ai_quality_breakdown: [
                'composition'         => $qualityScore,
                'luxury_appeal'      => (int) ($qualityScore * 0.9 + rand(0, 10)),
                'marketability'      => (int) ($qualityScore * 0.95 + rand(0, 5)),
                'professional_quality' => $qualityScore,
            ],
            overall_confidence: $avgConfidence,
            provider: 'mock',
            final_room_type: $roomType,
            fusion_confidence: $avgConfidence,
            raw_response: ['mock' => true, 'image' => $fileName],
        );
    }

    public function providerName(): string
    {
        return 'mock';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    // ─── Private ───────────────────────────────────────────────────────────────

    /** @return VisionObjectDTO[] */
    private function extractByKeywords(array $items, string $searchText): array
    {
        $found = [];

        foreach ($items as $item) {
            if (str_contains($searchText, $item['slug']) || str_contains($searchText, strtolower($item['label']))) {
                $found[] = new VisionObjectDTO(
                    type: 'amenity',
                    label: $item['label'],
                    confidence: (float) $item['confidence'],
                    provider: 'mock',
                    reason: "Mock: Dosya adında '{$item['label']}' tespit edildi.",
                    metadata: ['source' => 'keyword_match'],
                );
            }
        }

        return $found;
    }

    /** @return VisionObjectDTO[] */
    private function detectViews(string $searchText): array
    {
        $viewMap = [
            'manzara'  => ['slug' => 'deniz-manzarasi', 'label' => 'Deniz Manzarası', 'confidence' => 0.94],
            'sea'      => ['slug' => 'deniz-manzarasi', 'label' => 'Deniz Manzarası', 'confidence' => 0.93],
            'panorama' => ['slug' => 'panorama', 'label' => 'Panorama Manzara', 'confidence' => 0.92],
            'gunbatan' => ['slug' => 'gunbatimi', 'label' => 'Günbatımı Manzarası', 'confidence' => 0.91],
            'sunset'   => ['slug' => 'gunbatimi', 'label' => 'Günbatımı Manzarası', 'confidence' => 0.90],
        ];

        foreach ($viewMap as $keyword => $view) {
            if (str_contains($searchText, $keyword)) {
                return [new VisionObjectDTO(
                    type: 'manzara',
                    label: $view['label'],
                    confidence: (float) $view['confidence'],
                    provider: 'mock',
                    reason: "Mock: {$view['label']} dosya adından tespit edildi.",
                    metadata: ['keyword' => $keyword],
                )];
            }
        }

        return [];
    }

    /** @return VisionObjectDTO[] */
    private function detectStyle(string $searchText): array
    {
        $styleMap = [
            'modern'   => ['label' => 'Modern', 'confidence' => 0.90],
            'minimalist' => ['label' => 'Minimalist', 'confidence' => 0.87],
            'country'  => ['label' => 'Kır Evi Tarzı', 'confidence' => 0.83],
            'classic'  => ['label' => 'Klasik', 'confidence' => 0.81],
        ];

        foreach ($styleMap as $keyword => $style) {
            if (str_contains($searchText, $keyword)) {
                return [new VisionObjectDTO(
                    type: 'stil',
                    label: $style['label'],
                    confidence: (float) $style['confidence'],
                    provider: 'mock',
                    reason: "Mock: {$style['label']} tarzı dosya adından tespit edildi.",
                )];
            }
        }

        return [];
    }

    private function calcMockQualityScore(string $searchText): int
    {
        $score = 70;

        if (str_contains($searchText, 'profesyonel') || str_contains($searchText, 'hdr')) {
            $score += 20;
        }
        if (str_contains($searchText, 'yuksek') || str_contains($searchText, 'high')) {
            $score += 10;
        }
        if (str_contains($searchText, 'luks') || str_contains($searchText, 'luxury')) {
            $score += 15;
        }

        return min(100, $score);
    }
}
