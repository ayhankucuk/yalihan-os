<?php

namespace App\Services\Governance;

use App\Services\AI\SmartPropertyMatcherAI;
use App\Services\Cortex\MatchingEngine;
use App\Models\Talep;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🛡️ DecisionComparator
 * Phase 4D: Inference Leakage Guard
 * 
 * AI kararları ile "Saf SQL" kontrol grubunu karşılaştırarak "Inference Drift" hesaplar.
 */
class DecisionComparator
{
    public function __construct(
        protected MatchingEngine $cortexEngine,
        protected SmartPropertyMatcherAI $controlGroupEngine
    ) {}

    /**
     * İki motor arasındaki karar farkını analiz eder ve gerekirse Fallback uygular.
     * Phase 4 Final: Manual Authority Override (GCC Kill-Switch)
     */
    public function compareAndDecide(Talep $talep): array
    {
        // 0. Manual Authority Override (GCC Kill-Switch)
        $mode = Cache::get('governance:authority:mode', 'auto');
        
        if ($mode === 'forced_sql') {
            return [
                'final_decision_source' => 'CONTROL_GROUP (FORCED_SQL_OVERRIDE)',
                'results' => $this->getControlResults($talep),
                'is_precision_verified' => true
            ];
        }

        if ($mode === 'forced_ai') {
            return [
                'final_decision_source' => 'AI (FORCED_AI_OVERRIDE)',
                'results' => $this->cortexEngine->findMatchesForLead($talep),
                'is_precision_verified' => false
            ];
        }

        // 0.1 Categorical Emergency Fallback (Surgical Protection)
        // Mimarın Emri: Ticari gibi yüksek sapmalı kategorileri otomatik korumaya al
        if (($talep->kategori ?? '') === 'Ticari' || Cache::has('governance:fallback:ticari')) {
            return [
                'final_decision_source' => 'CONTROL_GROUP (CATEGORICAL_SAFETY_FALLBACK)',
                'results' => $this->getControlResults($talep),
                'is_precision_verified' => true
            ];
        }

        // 1. Congestion Awareness: Sunucu yükü çok yüksekse doğrulamayı atla
        if ($this->isCongested()) {
            return [
                'final_decision_source' => 'AI (CORTEX_ONLY_BYPASS)',
                'results' => $this->cortexEngine->findMatchesForLead($talep),
                'is_precision_verified' => false
            ];
        }

        // 2. Adaptive Sampling: Değerli bir talep değilse ve örnekleme girmiyorsa atla
        if (!$this->shouldVerify($talep)) {
            return [
                'final_decision_source' => 'AI (CORTEX_NO_SAMPLE)',
                'results' => $this->cortexEngine->findMatchesForLead($talep),
                'is_precision_verified' => false
            ];
        }

        $comparison = $this->compareMatchingDecisions($talep);

        // 🛡️ CIRCUIT BREAKER: Eğer drift çok yüksekse AI'yı devre dışı bırak
        $useFallback = $comparison['is_drift_detected'];
        
        return [
            'comparison' => $comparison,
            'final_decision_source' => $useFallback ? 'CONTROL_GROUP (SQL)' : 'AI (CORTEX)',
            'results' => $useFallback 
                ? $this->getControlResults($talep) 
                : $this->cortexEngine->findMatchesForLead($talep),
            'is_precision_verified' => true // Doğrulama yapıldı
        ];
    }

    /**
     * SQL Motoru Sonuçlarını getirir (Redis Caching - Phase 4E)
     */
    private function getControlResults(Talep $talep): array
    {
        $cacheKey = "match_control_group:" . md5($talep->id . $talep->butce_max . $talep->lat . $talep->lng);

        return Cache::remember($cacheKey, 3600, function() use ($talep) {
            Log::info('[Phase 4E] SQL Load Breaker: Cache Miss. Executing SQL Matching.', ['talep_id' => $talep->id]);
            return $this->controlGroupEngine->match($talep);
        });
    }

    /**
     * Mevcut talebin doğrulanıp doğrulanmayacağına karar verir.
     */
    private function shouldVerify(Talep $talep): bool
    {
        // High Value Threshold Check
        $threshold = config('governance.high_value_threshold', 500000);
        if ($talep->butce_max >= $threshold) {
            return true;
        }

        // Random Sampling Check
        $samplingRate = config('governance.drift_sampling_rate', 0.1);
        return (mt_rand() / mt_getrandmax()) <= $samplingRate;
    }

    /**
     * Sunucu yükünü kontrol eder (Phase 4E: Congestion Awareness)
     */
    private function isCongested(): bool
    {
        // Basitçe cache üzerinden bir bayrağa bakıyoruz (External monitor tarafından set edilebilir)
        // Veya DB aktif thread sayısı kontrol edilebilir.
        if (Cache::has('system:load:high')) {
            return true;
        }

        return false;
    }

    /**
     * İki motor arasındaki karar farkını analiz eder.
     */
    public function compareMatchingDecisions(Talep $talep): array
    {
        // 1. AI Decision (Cortex)
        $aiResults = $this->cortexEngine->findMatchesForLead($talep);
        $aiIds = collect($aiResults)->pluck('id')->toArray();

        // 2. Control Group Decision (Saf SQL/Weighted)
        $controlResults = $this->controlGroupEngine->match($talep);
        $controlIds = collect($controlResults)->pluck('ilan.id')->toArray();

        // 3. Calculate Drift
        $intersection = array_intersect($aiIds, $controlIds);
        $union = array_unique(array_merge($aiIds, $controlIds));
        
        $jaccardIndex = count($union) > 0 ? count($intersection) / count($union) : 1;
        $driftScore = 1 - $jaccardIndex;

        return [
            'talep_id' => $talep->id,
            'ai_count' => count($aiIds),
            'control_count' => count($controlIds),
            'intersection_count' => count($intersection),
            'jaccard_index' => round($jaccardIndex, 4),
            'inference_drift' => round($driftScore, 4),
            'is_drift_detected' => $driftScore > 0.3 || $this->calculateRankDrift($aiIds, $controlIds) > 0.5,
            'timestamp' => now()->toDateTimeString()
        ];
    }

    /**
     * Sıralama hassasiyetini (Rank Precision) hesaplar.
     * İlk 3 ilanın sırası değişmişse ağırlıklı ceza verir.
     */
    private function calculateRankDrift(array $aiIds, array $controlIds): float
    {
        $limit = min(3, count($aiIds), count($controlIds));
        if ($limit === 0) return 0;

        $mismatches = 0;
        for ($i = 0; $i < $limit; $i++) {
            if ($aiIds[$i] !== $controlIds[$i]) {
                $mismatches++;
            }
        }

        return $mismatches / $limit;
    }
}
