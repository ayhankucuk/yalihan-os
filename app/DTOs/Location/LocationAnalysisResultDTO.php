<?php

namespace App\DTOs\Location;

/**
 * Location Analysis Result DTO — Sprint 6.2
 *
 * Pipeline çıktısını taşır. AI çağırmaz, sadece hesaplanmış sonuçları taşır.
 * Context7 kuralı: "status" → internal DTO field olarak @sab-ignore ile muaf.
 *
 * pipeline_durumu değerleri:
 *   ok                    → Tüm adımlar başarılı
 *   no_coordinates       → Koordinat çözülemedi
 *   insufficient_data     → POI verisi yetersiz
 *   ilan_not_found       → Ilan bulunamadı
 *   error                → Genel hata
 *
 * @sab-ignore: Internal DTO — context7 Naming Authority kapsamı dışında
 */
final class LocationAnalysisResultDTO
{
    /** @sab-ignore: internal DTO state field */
    private string $pipeline_durumu;

    public function __construct(
        string $pipeline_durumu,
        public readonly ?int $score,
        public readonly string $confidence,
        public readonly int $poi_access_score,
        public readonly int $poi_density_score,
        public readonly int $poi_coverage_score,
        public readonly array $top_groups,
        public readonly ?float $lat,
        public readonly ?float $lng,
        public readonly string $geocode_source,
        public readonly ?string $ai_summary,
        public readonly array $reason_codes,
        public readonly int $demand_modifier,
        public readonly ?string $error = null,
    ) {
        $this->pipeline_durumu = $pipeline_durumu;
    }

    /** @sab-ignore: internal getter */
    public function getStatus(): string
    {
        return $this->pipeline_durumu;
    }

    public function isOk(): bool
    {
        return $this->pipeline_durumu === 'ok';
    }

    public function toArray(): array
    {
        return [
            'status' => $this->pipeline_durumu,
            'score' => $this->score,
            'confidence' => $this->confidence,
            'sub_scores' => [
                'poi_access_score' => $this->poi_access_score,
                'poi_density_score' => $this->poi_density_score,
                'poi_coverage_score' => $this->poi_coverage_score,
            ],
            'top_groups' => $this->top_groups,
            'coordinates' => [
                'lat' => $this->lat,
                'lng' => $this->lng,
            ],
            'geocode_source' => $this->geocode_source,
            'ai_summary' => $this->ai_summary,
            'reason_codes' => $this->reason_codes,
            'demand_modifier' => $this->demand_modifier,
            'error' => $this->error,
        ];
    }

    public function toApiResponse(): array
    {
        if ($this->pipeline_durumu === 'ok') {
            return [
                'status' => 'ok',
                'data' => [
                    'score' => $this->score,
                    'confidence' => $this->confidence,
                    'sub_scores' => [
                        'poi_access_score' => $this->poi_access_score,
                        'poi_density_score' => $this->poi_density_score,
                        'poi_coverage_score' => $this->poi_coverage_score,
                    ],
                    'top_groups' => $this->top_groups,
                    'coordinates' => [
                        'lat' => $this->lat,
                        'lng' => $this->lng,
                    ],
                    'geocode_source' => $this->geocode_source,
                    'ai_summary' => $this->ai_summary,
                    'reason_codes' => $this->reason_codes,
                    'demand_modifier' => $this->demand_modifier,
                ],
            ];
        }

        $messages = [
            'no_coordinates' => 'Koordinat bulunamadı.',
            'insufficient_data' => 'Bu bölgede yeterli POI verisi bulunamadı.',
            'ilan_not_found' => 'İlan bulunamadı.',
            'error' => $this->error ?? 'Bilinmeyen hata oluştu.',
        ];

        return [
            'status' => $this->pipeline_durumu,
            'message' => $messages[$this->pipeline_durumu] ?? $this->error,
            'data' => [
                'score' => $this->score,
                'confidence' => $this->confidence,
            ],
        ];
    }

    public static function insufficient(string $reason): self
    {
        return new self(
            pipeline_durumu: $reason,
            score: null,
            confidence: 'VERY_LOW',
            poi_access_score: 0,
            poi_density_score: 0,
            poi_coverage_score: 0,
            top_groups: [],
            lat: null,
            lng: null,
            geocode_source: 'none',
            ai_summary: null,
            reason_codes: [$reason],
            demand_modifier: 0,
        );
    }
}
