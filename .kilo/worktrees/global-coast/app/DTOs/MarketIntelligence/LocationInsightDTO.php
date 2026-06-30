<?php

namespace App\DTOs\MarketIntelligence;

/**
 * Location Insight DTO — MIE V4
 *
 * POI-tabanlı deterministik konum sinyali taşıyıcısı.
 * AI çağırmaz, skor üretmez, sadece hesaplanmış sonuçları taşır.
 *
 * Aralıklar:
 *   location_signal_score: 0–100 (null = insufficient data)
 *   poi_access_score:      0–40
 *   poi_density_score:     0–30
 *   poi_coverage_score:    0–30
 *   confidence_score:      0–100
 *   confidence_label:      HIGH | MEDIUM | LOW | VERY_LOW
 *   data_status:           ok | insufficient_location_data | no_coordinates
 */
final class LocationInsightDTO
{
    /**
     * @param int|null    $location_signal_score  Toplam konum sinyali (0–100), null = yetersiz veri
     * @param int         $confidence_score       Veri güven skoru (0–100)
     * @param string      $confidence_label       HIGH | MEDIUM | LOW | VERY_LOW
     * @param string      $data_status            ok | insufficient_location_data | no_coordinates
     * @param int         $poi_access_score       Kritik POI erişim skoru (0–40)
     * @param int         $poi_density_score      POI yoğunluk skoru (0–30)
     * @param int         $poi_coverage_score     POI çeşitlilik skoru (0–30)
     * @param array       $top_nearby_groups      En yakın gruplar [{group, label, closest_m, count}]
     * @param array       $reason_codes           Reason code listesi
     * @param string      $human_summary          Deterministic Türkçe özet
     * @param int         $demand_modifier        Demand score katkısı (capped)
     */
    public function __construct(
        public readonly ?int $location_signal_score,
        public readonly int $confidence_score,
        public readonly string $confidence_label,
        public readonly string $data_status,
        public readonly int $poi_access_score,
        public readonly int $poi_density_score,
        public readonly int $poi_coverage_score,
        public readonly array $top_nearby_groups,
        public readonly array $reason_codes,
        public readonly string $human_summary,
        public readonly int $demand_modifier = 0,
    ) {}

    public function isInsufficient(): bool
    {
        return $this->data_status !== 'ok';
    }

    public function toArray(): array
    {
        return [
            'location_signal_score' => $this->location_signal_score,
            'confidence_score' => $this->confidence_score,
            'confidence_label' => $this->confidence_label,
            'data_status' => $this->data_status,
            'poi_access_score' => $this->poi_access_score,
            'poi_density_score' => $this->poi_density_score,
            'poi_coverage_score' => $this->poi_coverage_score,
            'top_nearby_groups' => $this->top_nearby_groups,
            'reason_codes' => $this->reason_codes,
            'human_summary' => $this->human_summary,
            'demand_modifier' => $this->demand_modifier,
        ];
    }

    /**
     * Veri yetersizse güvenli fallback döner.
     */
    public static function insufficient(string $dataState = 'insufficient_location_data'): self
    {
        $reasonCode = $dataState === 'no_coordinates' ? 'no_coordinates' : 'insufficient_location_data';

        $defaultSummaries = [
            'no_coordinates' => 'İlan koordinatı bulunmadığı için konum değerlendirmesi yapılamadı.',
            'insufficient_location_data' => 'Konum verisi yetersiz, çevresel kıyas sinyali oluşturulamadı.',
        ];

        // Config varsa config'den oku, yoksa hardcoded fallback
        $summaryTemplates = [];
        try {
            $summaryTemplates = config('location_intelligence.summary_templates', []);
        } catch (\Throwable) {
            // Laravel container not available (pure unit tests)
        }

        $template = $dataState === 'no_coordinates' ? 'no_coordinates' : 'insufficient';
        $summary = $summaryTemplates[$template] ?? $defaultSummaries[$dataState] ?? 'Konum verisi yetersiz.';

        return new self(
            location_signal_score: null,
            confidence_score: 0,
            confidence_label: 'VERY_LOW',
            data_status: $dataState,
            poi_access_score: 0,
            poi_density_score: 0,
            poi_coverage_score: 0,
            top_nearby_groups: [],
            reason_codes: [$reasonCode],
            human_summary: $summary,
            demand_modifier: 0,
        );
    }
}
