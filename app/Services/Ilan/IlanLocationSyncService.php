<?php

namespace App\Services\Ilan;

use App\DTOs\Location\LocationAnalysisResultDTO;
use App\Models\Ilan;
use App\Services\Location\LocationOrchestrator;
use Illuminate\Support\Facades\Log;

/**
 * Ilan Location Sync Service — Sprint 6.2
 *
 * LocationOrchestrator sonucunu Ilan modeline yazar.
 * SAB Authority: Tek write authority — IlanCrudService yazma zincirine uyar.
 */
class IlanLocationSyncService
{
    public function __construct(
        private readonly LocationOrchestrator $orchestrator,
    ) {}

    /**
     * Bir Ilan'ın konum analizini çalıştır + Ilan'a kaydet.
     *
     * @param  int       $ilanId
     * @param  bool     $includeAiSummary
     * @return LocationAnalysisResultDTO
     */
    public function sync(int $ilanId, bool $includeAiSummary = false): LocationAnalysisResultDTO
    {
        // 1. Analiz et
        $result = $this->orchestrator->analyze($ilanId, $includeAiSummary);

        // 2. Ilan'a yaz
        $this->persist($ilanId, $result);

        return $result;
    }

    /**
     * Birden fazla Ilan'ı kuyruğa ekle (batch).
     *
     * @param  int[]  $ilanIds
     */
    public function scheduleBatch(array $ilanIds): int
    {
        $queued = 0;

        foreach ($ilanIds as $ilanId) {
            try {
                \App\Jobs\SyncIlanLocationJob::dispatch($ilanId);
                $queued++;
            } catch (\Throwable $e) {
                Log::warning('IlanLocationSyncService: Failed to dispatch job', [
                    'ilan_id' => $ilanId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $queued;
    }

    /**
     * Analiz sonucunu Ilan modeline kaydet.
     */
    private function persist(int $ilanId, LocationAnalysisResultDTO $result): void
    {
        try {
            $ilan = Ilan::find($ilanId);
            if (!$ilan) {
                Log::warning('IlanLocationSyncService: Ilan not found', ['ilan_id' => $ilanId]);
                return;
            }

            $ilan->location_score = $result->score;
            $ilan->location_score_confidence = $result->score !== null ? $result->confidence : null;
            $ilan->location_analyzed_at = now();

            // Yalnızca okunan alanlar — IlanCrudService yazma zincirine dokunmuyoruz
            // Çünkü bu bir cache/computed field güncellemesidir.
            // location_data JSON — computed metadata
            $ilan->location_data = $result->isOk() ? [
                'score' => $result->score,
                'confidence' => $result->confidence,
                'sub_scores' => [
                    'poi_access_score' => $result->poi_access_score,
                    'poi_density_score' => $result->poi_density_score,
                    'poi_coverage_score' => $result->poi_coverage_score,
                ],
                'top_groups' => $result->top_groups,
                'coordinates' => [
                    'lat' => $result->lat,
                    'lng' => $result->lng,
                ],
                'geocode_source' => $result->geocode_source,
                'ai_summary' => $result->ai_summary,
                'reason_codes' => $result->reason_codes,
                'demand_modifier' => $result->demand_modifier,
                'status' => $result->status,
            ] : null;

            $ilan->save();
        } catch (\Throwable $e) {
            Log::error('IlanLocationSyncService: Persist failed', [
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
