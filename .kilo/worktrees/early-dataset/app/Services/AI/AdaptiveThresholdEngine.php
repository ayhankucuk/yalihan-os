<?php

namespace App\Services\AI;

use App\Models\AiEsikProfili;
use App\Models\AiOgrenmeSinyali;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🎯 Adaptive Threshold Engine
 * Phase 9: Dynamically adjusts AI confidence thresholds based on real-world performance
 * Updated Phase 10: Supports categorical overrides from config
 */
class AdaptiveThresholdEngine
{
    /**
     * Recalculate and update all threshold profiles
     */
    public function recalculateAll(): int
    {
        $summary = DB::table('ai_ogrenme_sinyalleri')
            ->select(
                'kategori_id',
                'yayin_tipi_id',
                DB::raw('COUNT(*) as total_samples'),
                DB::raw('SUM(CASE WHEN karar_tipi = "applied" THEN 1 ELSE 0 END) as applied_count'),
                DB::raw('SUM(CASE WHEN karar_tipi = "dismissed" OR karar_tipi = "auto_reverted" THEN 1 ELSE 0 END) as rejected_count')
            )
            ->groupBy('kategori_id', 'yayin_tipi_id')
            ->get();

        $updatedCount = 0;

        foreach ($summary as $row) {
            if ($row->total_samples >= 50) { 
                if ($this->updateProfile($row)) {
                    $updatedCount++;
                }
            }
        }

        return $updatedCount;
    }

    /**
     * Update a specific threshold profile based on performance
     */
    protected function updateProfile($data): bool
    {
        $acceptRate = $data->applied_count / ($data->applied_count + $data->rejected_count ?: 1);
        
        $profile = AiEsikProfili::firstOrCreate(
            [
                'kategori_id' => $data->kategori_id,
                'yayin_tipi_id' => $data->yayin_tipi_id,
                'saglayici' => 'global'
            ],
            [
                'auto_apply_esigi' => $this->getFallbackAutoThreshold($data->kategori_id),
                'suggest_esigi' => config('ai-governance.global.suggest_min_confidence', 0.50),
                'min_ornek_sayisi' => 50
            ]
        );

        $currentAuto = (float) $profile->auto_apply_esigi;
        $newAuto = $currentAuto;

        if ($acceptRate < 0.30) {
            $newAuto += 0.05;
        } elseif ($acceptRate > 0.75) {
            $newAuto -= 0.03;
        }

        $newSuggest = $newAuto - 0.25;
        $newAuto = max(0.60, min(0.95, $newAuto));
        $newSuggest = max(0.30, min(0.80, $newSuggest));

        if (abs($newAuto - $currentAuto) > 0.001) {
            $profile->update([
                'auto_apply_esigi' => $newAuto,
                'suggest_esigi' => $newSuggest
            ]);
            return true;
        }

        return false;
    }

    /**
     * Get the active threshold for a specific context
     */
    public function getActiveThresholds(?int $categoryId, ?int $yayinTipiId): array
    {
        // 1. Check for Continuous Optimization Overrides (Step 11.2)
        $override = \App\Models\AiThresholdOverride::where('kategori_id', $categoryId)
            ->where('yayin_tipi_id', $yayinTipiId)
            ->first();

        if ($override) {
            return [
                'auto_apply' => (float) $override->auto_apply_threshold,
                'suggest' => (float) $override->suggest_threshold,
                'source' => 'continuous_optimization_override'
            ];
        }

        // 2. Fallback to older adaptive profile structure
        $profile = AiEsikProfili::where('kategori_id', $categoryId)
            ->where('yayin_tipi_id', $yayinTipiId)
            ->first();

        if ($profile) {
            return [
                'auto_apply' => (float) $profile->auto_apply_esigi,
                'suggest' => (float) $profile->suggest_esigi,
                'source' => 'adaptive_profile'
            ];
        }

        // 3. Fallback to categorical or global governance config
        return [
            'auto_apply' => $this->getFallbackAutoThreshold($categoryId),
            'suggest' => config('ai-governance.global.suggest_min_confidence', 0.50),
            'source' => 'governance_config'
        ];
    }

    /**
     * Get fallback threshold from config with categorical support
     */
    protected function getFallbackAutoThreshold(?int $categoryId): float
    {
        $global = (float) config('ai-governance.global.auto_apply_min_confidence', 0.80);
        
        if (!$categoryId) return $global;

        $categorySlug = $this->getCategorySlug($categoryId);
        $overrides = config('ai-governance.category_overrides', []);

        return (float) ($overrides[$categorySlug]['auto_apply_min_confidence'] ?? $global);
    }

    /**
     * Helper to resolve category slug from ID
     */
    protected function getCategorySlug(?int $categoryId): string
    {
        if (!$categoryId) return 'unknown';

        $map = [
            1 => 'konut',
            2 => 'isyeri',
            3 => 'arsa',
            4 => 'turistik',
            5 => 'yazlik',
        ];

        return $map[$categoryId] ?? 'other';
    }
}
