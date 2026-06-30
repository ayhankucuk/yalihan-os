<?php

namespace App\Services\AI;

use App\Models\AiFeatureUsage;
use App\Models\AiOgrenmeSinyali;
use Illuminate\Support\Facades\Log;

/**
 * 💡 AI Learning Signal Service
 * Phase 9: Translates telemetry into learning data for adaptive thresholds
 */
class AiLearningSignalService
{
    /**
     * Record a learning signal from user feedback or auto-action
     */
    public function recordSignal(AiFeatureUsage $usage, string $karar): ?AiOgrenmeSinyali
    {
        try {
            $skor = $this->computeScore($karar);
            $contextHash = $this->makeContextHash($usage);

            return AiOgrenmeSinyali::create([
                'ai_feature_usage_id' => $usage->id,
                'kategori_id' => $usage->kategori_id,
                'yayin_tipi_id' => $usage->yayin_tipi_id,
                'feature_slug' => $usage->feature_slug,
                'confidence' => $usage->confidence,
                'karar_tipi' => $karar,
                'skor' => $skor,
                'context_hash' => $contextHash,
                'sinyaller_json' => $usage->neden_detay['signals'] ?? [],
            ]);
        } catch (\Exception $e) {
            Log::error('AI Learning Signal record failed', [
                'usage_id' => $usage->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Step 1.1: Decision to Score mapping
     * applied: +1, dismissed: -1, auto_reverted: -2
     */
    protected function computeScore(string $karar): int
    {
        return match ($karar) {
            'applied' => 1,
            'dismissed' => -1,
            'auto_reverted' => -2,
            'auto_applied' => 0, // Base marker, no learning value until user feedback
            default => 0,
        };
    }

    /**
     * Create a context hash to group similar operational conditions
     */
    protected function makeContextHash(AiFeatureUsage $usage): string
    {
        $payload = [
            'k' => $usage->kategori_id,
            'y' => $usage->yayin_tipi_id,
            's' => $usage->feature_slug,
            'p' => $usage->neden_detay['provider'] ?? 'unknown',
        ];

        return hash('sha256', json_encode($payload));
    }
}
