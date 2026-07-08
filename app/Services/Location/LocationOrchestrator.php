<?php

namespace App\Services\Location;

use App\DTOs\Location\GeocodingResultDTO;
use App\DTOs\Location\LocationAnalysisResultDTO;
use App\DTOs\MarketIntelligence\LocationInsightDTO;
use App\Models\Ilan;
use App\Services\AI\YalihanCortex;
use App\Services\MarketIntelligence\LocationIntelligenceService;
use Illuminate\Support\Facades\Log;

/**
 * Location Intelligence Orchestrator — Sprint 6.2
 *
 * Tek giriş noktası: bir Ilan'ın konum analizini baştan sona yürütür.
 *
 * Pipeline:
 *   Ilan → Adres Topla → Geocoding → POI Analysis → AI Summary → Result
 *
 * SAB Authority: Ilan modeline sadece IlanLocationSyncService yazar.
 * Bu orchestrator sadece okur ve sonuç döndürür (write yapmaz).
 */
class LocationOrchestrator
{
    public function __construct(
        private readonly GeocodingService $geocodingService,
        private readonly LocationIntelligenceService $locationIntelligence,
        private readonly ?YalihanCortex $cortex = null,
    ) {}

    /**
     * Bir Ilan'ın konum analizini çalıştır.
     *
     * @param  int       $ilanId
     * @param  bool      $includeAiSummary  AI summary üretilsin mi (default: false)
     * @return LocationAnalysisResultDTO
     */
    public function analyze(int $ilanId, bool $includeAiSummary = false): LocationAnalysisResultDTO
    {
        // Step 1: Ilan'ı al (relation'larla birlikte)
        $ilan = Ilan::with(['il', 'ilce', 'mahalle', 'ulke'])->findOrFail($ilanId);

        // Step 2: Adres string oluştur
        $addressText = $this->buildAddressString($ilan);

        // Step 3: Koordinat çöz
        $geoResult = $this->resolveCoordinates($ilan, $addressText);

        if (!$geoResult->success || $geoResult->lat === null || $geoResult->lng === null) {
            return LocationAnalysisResultDTO::insufficient('no_coordinates');
        }

        // Step 4: POI analizi
        $insight = $this->locationIntelligence->analyze($geoResult->lat, $geoResult->lng);

        if ($insight->isInsufficient()) {
            return $this->buildFromInsight(
                status: 'insufficient_data',
                insight: $insight,
                geoResult: $geoResult,
                aiSummary: null,
            );
        }

        // Step 5: AI summary (isteğe bağlı)
        $aiSummary = null;
        if ($includeAiSummary && $this->cortex !== null) {
            $aiSummary = $this->generateAiSummary($ilan, $insight, $geoResult);
        }

        // Step 6: Return
        return $this->buildFromInsight(
            status: 'ok',
            insight: $insight,
            geoResult: $geoResult,
            aiSummary: $aiSummary,
        );
    }

    /**
     * Sadece geocoding + POI score döndür (hızlı, no AI).
     *
     * @return LocationAnalysisResultDTO
     */
    public function analyzeQuick(int $ilanId): LocationAnalysisResultDTO
    {
        return $this->analyze($ilanId, includeAiSummary: false);
    }

    /**
     * Sadece score + confidence döndür (en hızlı, cache için uygun).
     *
     * @return array{ilan_id: int, score: int|null, confidence: string, demand_modifier: int, last_analyzed_at: string|null}
     */
    public function getScoreSummary(int $ilanId): array
    {
        $ilan = Ilan::findOrFail($ilanId);

        return [
            'ilan_id' => $ilanId,
            'score' => $ilan->location_score,
            'confidence' => $ilan->location_score_confidence ?? 'VERY_LOW',
            'demand_modifier' => $this->extractDemandModifier($ilan->location_data),
            'last_analyzed_at' => $ilan->location_analyzed_at?->toIso8601String(),
        ];
    }

    /**
     * Koordinat çözümlemesi yap (Ilan + adres string ile).
     */
    private function resolveCoordinates(Ilan $ilan, string $addressText): GeocodingResultDTO
    {
        // Önce Ilan'ın kendi koordinatlarını kontrol et
        if ($ilan->lat && $ilan->lng && $ilan->lat != 0 && $ilan->lng != 0) {
            return new GeocodingResultDTO(
                success: true,
                lat: (float) $ilan->lat,
                lng: (float) $ilan->lng,
                source: 'manual',
                displayName: $addressText,
                rawData: null,
                error: null,
            );
        }

        // Adres string ile Nominatim çözümle
        if (!empty($addressText)) {
            $result = $this->geocodingService->resolve($addressText);
            if ($result->success) {
                return $result;
            }
        }

        // Fallback: il/ilçe/mahalle ID'lerinden çöz
        if ($ilan->il_id && $ilan->ilce_id && $ilan->mahalle_id) {
            return $this->geocodingService->resolveFromIds(
                (int) $ilan->il_id,
                (int) $ilan->ilce_id,
                (int) $ilan->mahalle_id,
            );
        }

        return GeocodingResultDTO::failure('Koordinat çözülemedi');
    }

    /**
     * Ilan'dan tek satır adres string oluştur.
     */
    private function buildAddressString(Ilan $ilan): string
    {
        $parts = [];

        // İlçe/mahalle adlarını relation'lardan al
        if ($ilan->mahalle?->mahalle_adi) {
            $parts[] = $ilan->mahalle->mahalle_adi . ' Mah.';
        } elseif ($ilan->mahalle) {
            $parts[] = $ilan->mahalle . ' Mah.';
        }

        if ($ilan->ilce?->ilce_adi) {
            $parts[] = $ilan->ilce->ilce_adi;
        } elseif ($ilan->ilce) {
            $parts[] = $ilan->ilce;
        }

        if ($ilan->il?->il_adi) {
            $parts[] = $ilan->il->il_adi;
        } elseif ($ilan->il) {
            $parts[] = $ilan->il;
        }

        if ($ilan->ulke?->ulke_adi) {
            $parts[] = $ilan->ulke->ulke_adi;
        }

        // Son çare: `adres` kolonu
        if (empty($parts) && !empty($ilan->adres)) {
            return $ilan->adres;
        }

        if (empty($parts)) {
            $parts[] = 'Türkiye';
        }

        return implode(', ', array_filter($parts));
    }

    /**
     * AI summary üret (isteğe bağlı).
     */
    private function generateAiSummary(
        Ilan $ilan,
        LocationInsightDTO $insight,
        GeocodingResultDTO $geoResult,
    ): ?string {
        try {
            $topGroups = $insight->top_nearby_groups;
            $topLabels = array_slice(
                array_map(fn($g) => $g['label'] ?? '', $topGroups),
                0,
                3,
            );
            $groupsStr = implode(', ', array_filter($topLabels)) ?: 'çevresel hizmetler';

            $prompt = "Bu gayrimenkul {$insight->location_signal_score} konum puanına sahip. "
                . "En yakın özellikler: {$groupsStr}. "
                . "Koordinat: {$geoResult->lat}, {$geoResult->lng}. "
                . "Türkçe olarak 2 cümle ile konum avantajlarını özetle. "
                . "Sadece özet metni ver, başka bir şey yazma.";

            $response = $this->cortex->complete($prompt, [
                'model' => 'deepseek',
                'max_tokens' => 100,
            ]);

            return $response['text'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('LocationOrchestrator: AI summary failed', [
                'ilan_id' => $ilan->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function buildFromInsight(
        string $status,
        LocationInsightDTO $insight,
        GeocodingResultDTO $geoResult,
        ?string $aiSummary,
    ): LocationAnalysisResultDTO {
        return new LocationAnalysisResultDTO(
            status: $status,
            score: $insight->location_signal_score,
            confidence: $insight->confidence_label,
            poi_access_score: $insight->poi_access_score,
            poi_density_score: $insight->poi_density_score,
            poi_coverage_score: $insight->poi_coverage_score,
            top_groups: $insight->top_nearby_groups,
            lat: $geoResult->lat,
            lng: $geoResult->lng,
            geocode_source: $geoResult->source,
            ai_summary: $aiSummary ?? $insight->human_summary,
            reason_codes: $insight->reason_codes,
            demand_modifier: $insight->demand_modifier,
        );
    }

    private function extractDemandModifier(?array $locationData): int
    {
        if ($locationData === null) {
            return 0;
        }
        return (int) ($locationData['demand_modifier'] ?? 0);
    }
}
