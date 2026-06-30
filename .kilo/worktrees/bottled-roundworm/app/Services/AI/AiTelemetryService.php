<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AiExperiment;

/**
 * @deprecated Use App\Services\AI\Monitoring\AiTelemetryService instead (correlation ID support, resilience events)
 * This class exists only for backward compatibility and will be removed in future versions.
 */
class AiTelemetryService
{
    /**
     * Log feature usage for AI telemetry
     *
     * @param array $payload {
     *   kategori_id: int,
     *   yayin_tipi_id: int,
     *   feature_slug: string,
     *   confidence: float,
     *   source_tipi: string,
     *   aksiyon: string,
     *   neden: string|null,
     *   neden_detay: array|null,
     *   explainability_v2: array|null,
     *   istek_id: string|null,
     *   ilan_id: int|null,
     *   etkilesim_suresi_ms: int|null,
     *   deney_id: int|null,
     *   deney_varyasyon_anahtari: string|null
     * }
     */
    public function logFeatureUsage(array $payload): void
    {
        // Validate required fields
        $required = ['kategori_id', 'yayin_tipi_id', 'feature_slug', 'confidence', 'source_tipi', 'aksiyon'];

        foreach ($required as $field) {
            if (!isset($payload[$field])) {
                Log::warning('AI Telemetry: Missing required field', [
                    'field' => $field,
                    'payload' => $payload
                ]);
                return; // No-op if validation fails
            }
        }

        // Phase 10: Efficiency Logic
        $tahminiTasarruf = 0;
        if ($payload['aksiyon'] === 'auto_applied') {
            // Standard saving per auto-apply: ~30 seconds (feature assessment + selection)
            $tahminiTasarruf = 30.00;
        }

        // Phase 10: Auto-detection of experiment if not provided
        $deneyId = $payload['deney_id'] ?? null;
        $varyasyon = $payload['deney_varyasyon_anahtari'] ?? null;

        try {
            DB::table('ai_feature_usages')->insert([
                'ilan_id' => $payload['ilan_id'] ?? null,
                'kategori_id' => $payload['kategori_id'],
                'yayin_tipi_id' => $payload['yayin_tipi_id'],
                'feature_slug' => $payload['feature_slug'],
                'confidence' => $payload['confidence'],
                'source_tipi' => $payload['source_tipi'],
                'aksiyon' => $payload['aksiyon'],
                'neden' => $payload['neden'] ?? null,
                'neden_detay' => isset($payload['neden_detay']) ? json_encode($payload['neden_detay']) : null,
                'explainability_v2_json' => isset($payload['explainability_v2']) ? json_encode($payload['explainability_v2']) : null,
                'istek_id' => $payload['istek_id'] ?? null,

                // Phase 10 & 11 Fields
                'deney_id' => $deneyId,
                'deney_varyasyon_anahtari' => $varyasyon,
                'etkilesim_suresi_ms' => $payload['etkilesim_suresi_ms'] ?? null,
                'latency_ms' => $payload['latency_ms'] ?? null,
                'cache_hit' => $payload['cache_hit'] ?? false,
                'provider' => $payload['provider'] ?? null,
                'tahmini_tasarruf_sn' => $tahminiTasarruf,
                'maliyet_usd' => $payload['maliyet_usd'] ?? null,

                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('AI Telemetry logged', [
                'feature' => $payload['feature_slug'],
                'aksiyon' => $payload['aksiyon'],
                'saved' => $tahminiTasarruf
            ]);
        } catch (\Exception $e) {
            Log::error('AI Telemetry logging failed', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
        }
    }
}
